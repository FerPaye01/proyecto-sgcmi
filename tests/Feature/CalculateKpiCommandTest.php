<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\KpiDefinition;
use App\Models\KpiValue;
use App\Models\VesselCall;
use App\Models\Vessel;
use App\Models\Berth;
use App\Models\Appointment;
use App\Models\Truck;
use App\Models\Company;
use App\Models\GateEvent;
use App\Models\Gate;
use App\Models\Tramite;
use App\Models\Entidad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculateKpiCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed KPI definitions
        KpiDefinition::create([
            'code' => 'turnaround_h',
            'name' => 'Turnaround Time (horas)',
            'description' => 'Tiempo total de permanencia de nave en puerto',
        ]);
        
        KpiDefinition::create([
            'code' => 'espera_camion_h',
            'name' => 'Tiempo Espera Camión (horas)',
            'description' => 'Tiempo promedio de espera de camiones',
        ]);
        
        KpiDefinition::create([
            'code' => 'cumpl_citas_pct',
            'name' => 'Cumplimiento Citas (%)',
            'description' => 'Porcentaje de citas cumplidas a tiempo',
        ]);
        
        KpiDefinition::create([
            'code' => 'tramites_ok_pct',
            'name' => 'Trámites Completos (%)',
            'description' => 'Porcentaje de trámites completados sin incidencias',
        ]);
    }

    public function test_command_calculates_turnaround_kpi(): void
    {
        // Arrange: Crear vessel call finalizada hoy
        $berth = Berth::factory()->create();
        $vessel = Vessel::factory()->create();
        
        // ATA ayer, ATD hoy (24 horas de turnaround)
        $ata = now()->subDay()->startOfDay()->addHours(8);
        $atd = now()->startOfDay()->addHours(8);
        
        VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'berth_id' => $berth->id,
            'ata' => $ata,
            'atd' => $atd,
        ]);

        // Act: Ejecutar comando
        $this->artisan('kpi:calculate', ['--period' => 'today'])
            ->assertExitCode(0);

        // Assert: Verificar que se creó el KPI
        $kpiDef = KpiDefinition::where('code', 'turnaround_h')->first();
        $kpiValue = KpiValue::where('kpi_id', $kpiDef->id)
            ->where('periodo', now()->toDateString())
            ->first();

        $this->assertNotNull($kpiValue);
        $this->assertEquals(24.0, $kpiValue->valor);
        $this->assertEquals('portuario.vessel_call', $kpiValue->fuente);
    }

    public function test_command_calculates_waiting_time_kpi(): void
    {
        // Arrange: Crear appointment con tiempo de espera
        $company = Company::factory()->create();
        $truck = Truck::factory()->create(['company_id' => $company->id]);
        $gate = Gate::factory()->create();
        
        $horaLlegada = now()->startOfDay()->addHours(10);
        $horaEntrada = now()->startOfDay()->addHours(12); // 2 horas de espera
        
        $appointment = Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'hora_llegada' => $horaLlegada,
            'estado' => 'ATENDIDA',
        ]);
        
        GateEvent::factory()->create([
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
            'cita_id' => $appointment->id,
            'action' => 'ENTRADA',
            'event_ts' => $horaEntrada,
        ]);

        // Act: Ejecutar comando
        $this->artisan('kpi:calculate', ['--period' => 'today'])
            ->assertExitCode(0);

        // Assert: Verificar que se creó el KPI
        $kpiDef = KpiDefinition::where('code', 'espera_camion_h')->first();
        $kpiValue = KpiValue::where('kpi_id', $kpiDef->id)
            ->where('periodo', now()->toDateString())
            ->first();

        $this->assertNotNull($kpiValue);
        $this->assertEquals(2.0, $kpiValue->valor);
        $this->assertEquals('terrestre.appointment', $kpiValue->fuente);
    }

    public function test_command_calculates_appointment_compliance_kpi(): void
    {
        // Arrange: Crear appointments con diferentes clasificaciones
        $company = Company::factory()->create();
        $truck = Truck::factory()->create(['company_id' => $company->id]);
        
        $horaProgramada = now()->startOfDay()->addHours(10);
        
        // A tiempo (±15 min)
        Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'hora_programada' => $horaProgramada,
            'hora_llegada' => $horaProgramada->copy()->addMinutes(10),
            'estado' => 'ATENDIDA',
        ]);
        
        // Tarde (>15 min)
        Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'hora_programada' => $horaProgramada,
            'hora_llegada' => $horaProgramada->copy()->addMinutes(30),
            'estado' => 'ATENDIDA',
        ]);

        // Act: Ejecutar comando
        $this->artisan('kpi:calculate', ['--period' => 'today'])
            ->assertExitCode(0);

        // Assert: Verificar que se creó el KPI (50% cumplimiento)
        $kpiDef = KpiDefinition::where('code', 'cumpl_citas_pct')->first();
        $kpiValue = KpiValue::where('kpi_id', $kpiDef->id)
            ->where('periodo', now()->toDateString())
            ->first();

        $this->assertNotNull($kpiValue);
        $this->assertEquals(50.0, $kpiValue->valor);
        $this->assertEquals('terrestre.appointment', $kpiValue->fuente);
    }

    public function test_command_calculates_customs_completion_kpi(): void
    {
        // Arrange: Crear trámites finalizados
        $entidad = Entidad::factory()->create();
        $berth = Berth::factory()->create();
        $vessel = Vessel::factory()->create();
        $vesselCall = VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'berth_id' => $berth->id,
        ]);
        
        // Aprobado
        Tramite::factory()->create([
            'vessel_call_id' => $vesselCall->id,
            'entidad_id' => $entidad->id,
            'estado' => 'APROBADO',
            'fecha_inicio' => now()->subDays(2),
            'fecha_fin' => now(),
        ]);
        
        // Rechazado
        Tramite::factory()->create([
            'vessel_call_id' => $vesselCall->id,
            'entidad_id' => $entidad->id,
            'estado' => 'RECHAZADO',
            'fecha_inicio' => now()->subDays(2),
            'fecha_fin' => now(),
        ]);

        // Act: Ejecutar comando
        $this->artisan('kpi:calculate', ['--period' => 'today'])
            ->assertExitCode(0);

        // Assert: Verificar que se creó el KPI (50% aprobados)
        $kpiDef = KpiDefinition::where('code', 'tramites_ok_pct')->first();
        $kpiValue = KpiValue::where('kpi_id', $kpiDef->id)
            ->where('periodo', now()->toDateString())
            ->first();

        $this->assertNotNull($kpiValue);
        $this->assertEquals(50.0, $kpiValue->valor);
        $this->assertEquals('aduanas.tramite', $kpiValue->fuente);
    }

    public function test_command_does_not_recalculate_without_force(): void
    {
        // Arrange: Crear un KPI value existente
        $kpiDef = KpiDefinition::where('code', 'turnaround_h')->first();
        KpiValue::create([
            'kpi_id' => $kpiDef->id,
            'periodo' => now()->toDateString(),
            'valor' => 99.99,
            'meta' => 48.0,
            'fuente' => 'test',
        ]);

        // Act: Ejecutar comando sin --force
        $this->artisan('kpi:calculate', ['--period' => 'today'])
            ->assertExitCode(0);

        // Assert: El valor no debe cambiar
        $kpiValue = KpiValue::where('kpi_id', $kpiDef->id)
            ->where('periodo', now()->toDateString())
            ->first();

        $this->assertEquals(99.99, $kpiValue->valor);
    }

    public function test_command_recalculates_with_force_option(): void
    {
        // Arrange: Crear un KPI value existente y datos reales
        $kpiDef = KpiDefinition::where('code', 'turnaround_h')->first();
        KpiValue::create([
            'kpi_id' => $kpiDef->id,
            'periodo' => now()->toDateString(),
            'valor' => 99.99,
            'meta' => 48.0,
            'fuente' => 'test',
        ]);
        
        $berth = Berth::factory()->create();
        $vessel = Vessel::factory()->create();
        VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'berth_id' => $berth->id,
            'ata' => now()->subDay()->startOfDay()->addHours(8),
            'atd' => now()->startOfDay()->addHours(8), // 24 horas, finaliza hoy
        ]);

        // Act: Ejecutar comando con --force
        $this->artisan('kpi:calculate', ['--period' => 'today', '--force' => true])
            ->assertExitCode(0);

        // Assert: El valor debe actualizarse
        $kpiValue = KpiValue::where('kpi_id', $kpiDef->id)
            ->where('periodo', now()->toDateString())
            ->first();

        $this->assertEquals(24.0, $kpiValue->valor);
    }

    public function test_command_handles_invalid_period(): void
    {
        // Act & Assert
        $this->artisan('kpi:calculate', ['--period' => 'invalid'])
            ->assertExitCode(1);
    }

    public function test_command_handles_no_data_gracefully(): void
    {
        // Act: Ejecutar comando sin datos
        $this->artisan('kpi:calculate', ['--period' => 'today'])
            ->assertExitCode(0);

        // Assert: No debe haber valores creados
        $count = KpiValue::where('periodo', now()->toDateString())->count();
        $this->assertEquals(0, $count);
    }
}
