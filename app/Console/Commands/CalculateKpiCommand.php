<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\KpiDefinition;
use App\Models\KpiValue;
use App\Models\VesselCall;
use App\Models\Appointment;
use App\Models\Tramite;
use App\Services\KpiCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Comando Artisan para cálculo batch de KPIs
 * Calcula y almacena KPIs agregados en analytics.kpi_value
 */
class CalculateKpiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kpi:calculate 
                            {--period=today : Periodo a calcular (today, yesterday, week, month)}
                            {--force : Forzar recálculo si ya existen valores}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calcula KPIs agregados y los almacena en analytics.kpi_value';

    private KpiCalculator $calculator;

    public function __construct(KpiCalculator $calculator)
    {
        parent::__construct();
        $this->calculator = $calculator;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $period = $this->option('period');
        $force = $this->option('force');

        $this->info("Iniciando cálculo de KPIs para periodo: {$period}");

        // Determinar fecha del periodo
        $periodoDate = $this->getPeriodoDate($period);
        
        if (!$periodoDate) {
            $this->error("Periodo inválido: {$period}");
            return Command::FAILURE;
        }

        $this->info("Calculando KPIs para fecha: {$periodoDate->toDateString()}");

        // Verificar si ya existen valores para este periodo
        if (!$force) {
            $existingCount = KpiValue::where('periodo', $periodoDate->toDateString())->count();
            if ($existingCount > 0) {
                $this->warn("Ya existen {$existingCount} valores de KPI para este periodo.");
                $this->warn("Use --force para recalcular.");
                return Command::SUCCESS;
            }
        }

        DB::beginTransaction();

        try {
            // Eliminar valores existentes si se fuerza el recálculo
            if ($force) {
                $deleted = KpiValue::where('periodo', $periodoDate->toDateString())->delete();
                if ($deleted > 0) {
                    $this->info("Eliminados {$deleted} valores existentes.");
                }
            }

            // Calcular cada KPI
            $this->calculateTurnaroundKpi($periodoDate);
            $this->calculateWaitingTimeKpi($periodoDate);
            $this->calculateAppointmentComplianceKpi($periodoDate);
            $this->calculateCustomsCompletionKpi($periodoDate);

            DB::commit();

            $this->info('✓ KPIs calculados exitosamente');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error al calcular KPIs: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    /**
     * Determina la fecha del periodo según el parámetro
     */
    private function getPeriodoDate(string $period): ?\Carbon\Carbon
    {
        return match ($period) {
            'today' => now(),
            'yesterday' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            default => null,
        };
    }

    /**
     * Calcula KPI de Turnaround promedio
     */
    private function calculateTurnaroundKpi(\Carbon\Carbon $periodoDate): void
    {
        $this->info('Calculando turnaround_h...');

        $kpiDef = KpiDefinition::where('code', 'turnaround_h')->first();
        
        if (!$kpiDef) {
            $this->warn('KPI turnaround_h no encontrado en definiciones');
            return;
        }

        // Obtener vessel_calls finalizadas en el periodo
        $vesselCalls = VesselCall::whereNotNull('ata')
            ->whereNotNull('atd')
            ->whereDate('atd', $periodoDate->toDateString())
            ->get();

        if ($vesselCalls->isEmpty()) {
            $this->warn('No hay vessel_calls finalizadas en el periodo');
            return;
        }

        $turnarounds = [];
        foreach ($vesselCalls as $vc) {
            $turnaround = $this->calculator->calculateTurnaround($vc->id);
            if ($turnaround !== null) {
                $turnarounds[] = $turnaround;
            }
        }

        if (empty($turnarounds)) {
            $this->warn('No se pudieron calcular turnarounds');
            return;
        }

        $avgTurnaround = array_sum($turnarounds) / count($turnarounds);

        KpiValue::create([
            'kpi_id' => $kpiDef->id,
            'periodo' => $periodoDate->toDateString(),
            'valor' => round($avgTurnaround, 4),
            'meta' => 48.0, // Meta: 48 horas
            'fuente' => 'portuario.vessel_call',
            'extra' => json_encode([
                'count' => count($turnarounds),
                'min' => min($turnarounds),
                'max' => max($turnarounds),
            ]),
        ]);

        $this->info("  ✓ turnaround_h: " . round($avgTurnaround, 2) . " horas (n={$vesselCalls->count()})");
    }

    /**
     * Calcula KPI de tiempo de espera promedio de camiones
     */
    private function calculateWaitingTimeKpi(\Carbon\Carbon $periodoDate): void
    {
        $this->info('Calculando espera_camion_h...');

        $kpiDef = KpiDefinition::where('code', 'espera_camion_h')->first();
        
        if (!$kpiDef) {
            $this->warn('KPI espera_camion_h no encontrado en definiciones');
            return;
        }

        // Obtener appointments atendidas en el periodo
        $appointments = Appointment::where('estado', 'ATENDIDA')
            ->whereNotNull('hora_llegada')
            ->whereDate('hora_llegada', $periodoDate->toDateString())
            ->get();

        if ($appointments->isEmpty()) {
            $this->warn('No hay appointments atendidas en el periodo');
            return;
        }

        $waitingTimes = [];
        foreach ($appointments as $appointment) {
            $waitingTime = $this->calculator->calculateWaitingTime($appointment->id);
            if ($waitingTime !== null) {
                $waitingTimes[] = $waitingTime;
            }
        }

        if (empty($waitingTimes)) {
            $this->warn('No se pudieron calcular tiempos de espera');
            return;
        }

        $avgWaitingTime = array_sum($waitingTimes) / count($waitingTimes);

        KpiValue::create([
            'kpi_id' => $kpiDef->id,
            'periodo' => $periodoDate->toDateString(),
            'valor' => round($avgWaitingTime, 4),
            'meta' => 2.0, // Meta: 2 horas
            'fuente' => 'terrestre.appointment',
            'extra' => json_encode([
                'count' => count($waitingTimes),
                'min' => min($waitingTimes),
                'max' => max($waitingTimes),
            ]),
        ]);

        $this->info("  ✓ espera_camion_h: " . round($avgWaitingTime, 2) . " horas (n={$appointments->count()})");
    }

    /**
     * Calcula KPI de cumplimiento de citas
     */
    private function calculateAppointmentComplianceKpi(\Carbon\Carbon $periodoDate): void
    {
        $this->info('Calculando cumpl_citas_pct...');

        $kpiDef = KpiDefinition::where('code', 'cumpl_citas_pct')->first();
        
        if (!$kpiDef) {
            $this->warn('KPI cumpl_citas_pct no encontrado en definiciones');
            return;
        }

        // Obtener appointments del periodo
        $appointments = Appointment::whereDate('hora_programada', $periodoDate->toDateString())
            ->get();

        if ($appointments->isEmpty()) {
            $this->warn('No hay appointments en el periodo');
            return;
        }

        $aTiempo = 0;
        $total = 0;

        foreach ($appointments as $appointment) {
            $compliance = $this->calculator->calculateAppointmentCompliance($appointment->id);
            if ($compliance['clasificacion'] === 'A_TIEMPO') {
                $aTiempo++;
            }
            $total++;
        }

        $pctCumplimiento = $total > 0 ? ($aTiempo / $total) * 100 : 0;

        KpiValue::create([
            'kpi_id' => $kpiDef->id,
            'periodo' => $periodoDate->toDateString(),
            'valor' => round($pctCumplimiento, 4),
            'meta' => 85.0, // Meta: 85%
            'fuente' => 'terrestre.appointment',
            'extra' => json_encode([
                'total' => $total,
                'a_tiempo' => $aTiempo,
                'tarde' => $total - $aTiempo,
            ]),
        ]);

        $this->info("  ✓ cumpl_citas_pct: " . round($pctCumplimiento, 2) . "% (n={$total})");
    }

    /**
     * Calcula KPI de trámites completados sin incidencias
     */
    private function calculateCustomsCompletionKpi(\Carbon\Carbon $periodoDate): void
    {
        $this->info('Calculando tramites_ok_pct...');

        $kpiDef = KpiDefinition::where('code', 'tramites_ok_pct')->first();
        
        if (!$kpiDef) {
            $this->warn('KPI tramites_ok_pct no encontrado en definiciones');
            return;
        }

        // Obtener trámites finalizados en el periodo
        $tramites = Tramite::whereNotNull('fecha_fin')
            ->whereDate('fecha_fin', $periodoDate->toDateString())
            ->get();

        if ($tramites->isEmpty()) {
            $this->warn('No hay trámites finalizados en el periodo');
            return;
        }

        $aprobados = $tramites->where('estado', 'APROBADO')->count();
        $total = $tramites->count();

        $pctOk = $total > 0 ? ($aprobados / $total) * 100 : 0;

        KpiValue::create([
            'kpi_id' => $kpiDef->id,
            'periodo' => $periodoDate->toDateString(),
            'valor' => round($pctOk, 4),
            'meta' => 90.0, // Meta: 90%
            'fuente' => 'aduanas.tramite',
            'extra' => json_encode([
                'total' => $total,
                'aprobados' => $aprobados,
                'rechazados' => $tramites->where('estado', 'RECHAZADO')->count(),
                'observados' => $tramites->where('estado', 'OBSERVADO')->count(),
            ]),
        ]);

        $this->info("  ✓ tramites_ok_pct: " . round($pctOk, 2) . "% (n={$total})");
    }
}
