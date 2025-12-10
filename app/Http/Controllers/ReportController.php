<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Berth;
use App\Models\Company;
use App\Models\Gate;
use App\Models\Vessel;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService
    ) {
    }

    /**
     * Reporte R1: Programación vs Ejecución
     * Compara tiempos programados (ETA/ETB) con tiempos reales (ATA/ATB/ATD)
     */
    public function r1(Request $request): View
    {
        // Construir filtros desde la request
        $filters = [];

        if ($request->filled('fecha_desde')) {
            $filters['fecha_desde'] = $request->input('fecha_desde');
        }

        if ($request->filled('fecha_hasta')) {
            $filters['fecha_hasta'] = $request->input('fecha_hasta');
        }

        if ($request->filled('berth_id')) {
            $filters['berth_id'] = (int) $request->input('berth_id');
        }

        if ($request->filled('vessel_id')) {
            $filters['vessel_id'] = (int) $request->input('vessel_id');
        }

        // Generar reporte
        $report = $this->reportService->generateR1($filters);

        // Obtener listas para filtros
        $berths = Berth::where('active', true)
            ->orderBy('name')
            ->get();

        $vessels = Vessel::orderBy('name')
            ->get();

        return view('reports.port.schedule-vs-actual', [
            'data' => $report['data'],
            'kpis' => $report['kpis'],
            'filters' => $filters,
            'berths' => $berths,
            'vessels' => $vessels,
        ]);
    }

    /**
     * Reporte R3: Utilización de Muelles
     * Calcula la utilización horaria de cada muelle basándose en ATB-ATD
     */
    public function r3(Request $request): View
    {
        // Construir filtros desde la request
        $filters = [];

        if ($request->filled('fecha_desde')) {
            $filters['fecha_desde'] = $request->input('fecha_desde');
        }

        if ($request->filled('fecha_hasta')) {
            $filters['fecha_hasta'] = $request->input('fecha_hasta');
        }

        if ($request->filled('berth_id')) {
            $filters['berth_id'] = (int) $request->input('berth_id');
        }

        if ($request->filled('franja_horas')) {
            $filters['franja_horas'] = (int) $request->input('franja_horas');
        }

        // Generar reporte
        $report = $this->reportService->generateR3($filters);

        // Obtener listas para filtros
        $berths = Berth::where('active', true)
            ->orderBy('name')
            ->get();

        // Preparar datos para tabla interactiva
        $tableData = $report['data']->map(function ($vesselCall) {
            $permanencia = null;
            if ($vesselCall->atb && $vesselCall->atd) {
                $permanencia = number_format(
                    ($vesselCall->atd->timestamp - $vesselCall->atb->timestamp) / 3600,
                    2
                );
            }

            return [
                'id' => $vesselCall->id,
                'nave' => $vesselCall->vessel->name ?? 'N/A',
                'viaje' => $vesselCall->viaje_id,
                'muelle' => $vesselCall->berth->name ?? 'N/A',
                'atb' => $vesselCall->atb?->format('Y-m-d H:i') ?? 'N/A',
                'atd' => $vesselCall->atd?->format('Y-m-d H:i') ?? 'N/A',
                'permanencia' => $permanencia,
                'estado' => $vesselCall->estado_llamada,
            ];
        })->toArray();

        $tableHeaders = [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'nave', 'label' => 'Nave', 'sortable' => true],
            ['key' => 'viaje', 'label' => 'Viaje', 'sortable' => true],
            ['key' => 'muelle', 'label' => 'Muelle', 'sortable' => true],
            ['key' => 'atb', 'label' => 'ATB', 'sortable' => true],
            ['key' => 'atd', 'label' => 'ATD', 'sortable' => true],
            [
                'key' => 'permanencia',
                'label' => 'Permanencia (h)',
                'sortable' => true,
                'format' => 'function(val) { return val ? val + "h" : "N/A"; }',
            ],
            [
                'key' => 'estado',
                'label' => 'Estado',
                'sortable' => true,
                'format' => 'function(val) {
                    const badges = {
                        "COMPLETADA": "<span class=\"badge-success\">COMPLETADA</span>",
                        "EN_CURSO": "<span class=\"badge-warning\">EN_CURSO</span>",
                        "PROGRAMADA": "<span class=\"badge-info\">PROGRAMADA</span>"
                    };
                    return badges[val] || "<span class=\"badge-info\">" + val + "</span>";
                }',
            ],
        ];

        return view('reports.port.berth-utilization', [
            'data' => $report['data'],
            'kpis' => $report['kpis'],
            'utilizacion_por_franja' => $report['utilizacion_por_franja'],
            'filters' => $filters,
            'berths' => $berths,
            'tableData' => $tableData,
            'tableHeaders' => $tableHeaders,
        ]);
    }

    /**
     * Reporte R4: Tiempo de Espera de Camiones
     * Calcula el tiempo de espera desde hora_llegada hasta el primer evento de gate
     * Aplica scoping por empresa para TRANSPORTISTA
     */
    public function r4(Request $request): View
    {
        // Construir filtros desde la request
        $filters = [];

        if ($request->filled('fecha_desde')) {
            $filters['fecha_desde'] = $request->input('fecha_desde');
        }

        if ($request->filled('fecha_hasta')) {
            $filters['fecha_hasta'] = $request->input('fecha_hasta');
        }

        if ($request->filled('company_id')) {
            $filters['company_id'] = (int) $request->input('company_id');
        }

        // Generar reporte con scoping
        $user = auth()->user();
        $report = $this->reportService->generateR4($filters, $user);

        // Obtener listas para filtros (solo si no es TRANSPORTISTA)
        $companies = collect();
        if ($user && !$user->hasRole('TRANSPORTISTA')) {
            $companies = Company::where('active', true)
                ->orderBy('name')
                ->get();
        }

        return view('reports.road.waiting-time', [
            'data' => $report['data'],
            'kpis' => $report['kpis'],
            'filters' => $filters,
            'companies' => $companies,
            'isTransportista' => $user ? $user->hasRole('TRANSPORTISTA') : false,
        ]);
    }

    /**
     * Reporte R5: Cumplimiento de Citas
     * Clasifica citas como: A tiempo (±15 min), Tarde (>15 min), No Show (sin llegada)
     * Aplica scoping por empresa para TRANSPORTISTA
     */
    public function r5(Request $request): View
    {
        // Construir filtros desde la request
        $filters = [];

        if ($request->filled('fecha_desde')) {
            $filters['fecha_desde'] = $request->input('fecha_desde');
        }

        if ($request->filled('fecha_hasta')) {
            $filters['fecha_hasta'] = $request->input('fecha_hasta');
        }

        if ($request->filled('company_id')) {
            $filters['company_id'] = (int) $request->input('company_id');
        }

        // Generar reporte con scoping
        $user = auth()->user();
        $report = $this->reportService->generateR5($filters, $user);

        // Obtener listas para filtros (solo si no es TRANSPORTISTA)
        $companies = collect();
        if ($user && !$user->hasRole('TRANSPORTISTA')) {
            $companies = Company::where('active', true)
                ->orderBy('name')
                ->get();
        }

        return view('reports.road.appointments-compliance', [
            'data' => $report['data'],
            'kpis' => $report['kpis'],
            'ranking' => $report['ranking'],
            'filters' => $filters,
            'companies' => $companies,
            'isTransportista' => $user ? $user->hasRole('TRANSPORTISTA') : false,
        ]);
    }

    /**
     * Reporte R6: Productividad de Gates
     * Calcula la productividad de cada gate basándose en eventos de entrada/salida
     */
    public function r6(Request $request): View
    {
        // Construir filtros desde la request
        $filters = [];

        if ($request->filled('fecha_desde')) {
            $filters['fecha_desde'] = $request->input('fecha_desde');
        }

        if ($request->filled('fecha_hasta')) {
            $filters['fecha_hasta'] = $request->input('fecha_hasta');
        }

        if ($request->filled('gate_id')) {
            $filters['gate_id'] = (int) $request->input('gate_id');
        }

        if ($request->filled('capacidad_teorica')) {
            $filters['capacidad_teorica'] = (int) $request->input('capacidad_teorica');
        }

        // Generar reporte
        $report = $this->reportService->generateR6($filters);

        // Obtener listas para filtros
        $gates = Gate::where('activo', true)
            ->orderBy('name')
            ->get();

        return view('reports.road.gate-productivity', [
            'data' => $report['data'],
            'kpis' => $report['kpis'],
            'productividad_por_hora' => $report['productividad_por_hora'],
            'filters' => $filters,
            'gates' => $gates,
        ]);
    }

    /**
     * Reporte R7: Estado de Trámites por Nave
     * Muestra el estado de trámites agrupados por llamada de nave
     * Calcula KPIs: pct_completos_pre_arribo, lead_time_h
     */
    public function r7(Request $request): View
    {
        // Construir filtros desde la request
        $filters = [];

        if ($request->filled('fecha_desde')) {
            $filters['fecha_desde'] = $request->input('fecha_desde');
        }

        if ($request->filled('fecha_hasta')) {
            $filters['fecha_hasta'] = $request->input('fecha_hasta');
        }

        if ($request->filled('vessel_id')) {
            $filters['vessel_id'] = (int) $request->input('vessel_id');
        }

        if ($request->filled('estado')) {
            $filters['estado'] = $request->input('estado');
        }

        if ($request->filled('entidad_id')) {
            $filters['entidad_id'] = (int) $request->input('entidad_id');
        }

        // Generar reporte
        $report = $this->reportService->generateR7($filters);

        // Obtener listas para filtros
        $vessels = Vessel::orderBy('name')->get();
        $entidades = \App\Models\Entidad::orderBy('name')->get();

        // Estados disponibles para filtro
        $estados = [
            'INICIADO' => 'Iniciado',
            'EN_REVISION' => 'En Revisión',
            'OBSERVADO' => 'Observado',
            'APROBADO' => 'Aprobado',
            'RECHAZADO' => 'Rechazado',
        ];

        return view('reports.cus.status-by-vessel', [
            'data' => $report['data'],
            'kpis' => $report['kpis'],
            'por_nave' => $report['por_nave'],
            'filters' => $filters,
            'vessels' => $vessels,
            'entidades' => $entidades,
            'estados' => $estados,
        ]);
    }

    /**
     * Reporte R8: Tiempo de Despacho por Régimen
     * Calcula percentiles de tiempo de despacho (p50, p90) y porcentaje fuera de umbral
     * Agrupa por régimen aduanero (IMPORTACION, EXPORTACION, TRANSITO)
     */
    public function r8(Request $request): View
    {
        // Construir filtros desde la request
        $filters = [];

        if ($request->filled('fecha_desde')) {
            $filters['fecha_desde'] = $request->input('fecha_desde');
        }

        if ($request->filled('fecha_hasta')) {
            $filters['fecha_hasta'] = $request->input('fecha_hasta');
        }

        if ($request->filled('regimen')) {
            $filters['regimen'] = $request->input('regimen');
        }

        if ($request->filled('entidad_id')) {
            $filters['entidad_id'] = (int) $request->input('entidad_id');
        }

        if ($request->filled('umbral_horas')) {
            $filters['umbral_horas'] = (int) $request->input('umbral_horas');
        }

        // Generar reporte
        $report = $this->reportService->generateR8($filters);

        // Obtener listas para filtros
        $entidades = \App\Models\Entidad::orderBy('name')->get();

        // Regímenes disponibles para filtro
        $regimenes = [
            'IMPORTACION' => 'Importación',
            'EXPORTACION' => 'Exportación',
            'TRANSITO' => 'Tránsito',
        ];

        return view('reports.cus.dispatch-time', [
            'data' => $report['data'],
            'kpis' => $report['kpis'],
            'por_regimen' => $report['por_regimen'],
            'filters' => $filters,
            'entidades' => $entidades,
            'regimenes' => $regimenes,
        ]);
    }

    /**
     * Reporte R9: Incidencias de Documentación
     * Analiza rechazos, reprocesamientos y tiempos de subsanación de trámites
     * Identifica problemas documentales y tiempos de corrección
     */
    public function r9(Request $request): View
    {
        // Construir filtros desde la request
        $filters = [];

        if ($request->filled('fecha_desde')) {
            $filters['fecha_desde'] = $request->input('fecha_desde');
        }

        if ($request->filled('fecha_hasta')) {
            $filters['fecha_hasta'] = $request->input('fecha_hasta');
        }

        if ($request->filled('regimen')) {
            $filters['regimen'] = $request->input('regimen');
        }

        if ($request->filled('entidad_id')) {
            $filters['entidad_id'] = (int) $request->input('entidad_id');
        }

        // Generar reporte
        $report = $this->reportService->generateR9($filters);

        // Obtener listas para filtros
        $entidades = \App\Models\Entidad::orderBy('name')->get();

        // Regímenes disponibles para filtro
        $regimenes = [
            'IMPORTACION' => 'Importación',
            'EXPORTACION' => 'Exportación',
            'TRANSITO' => 'Tránsito',
        ];

        return view('reports.cus.doc-incidents', [
            'data' => $report['data'],
            'kpis' => $report['kpis'],
            'por_entidad' => $report['por_entidad'],
            'filters' => $filters,
            'entidades' => $entidades,
            'regimenes' => $regimenes,
        ]);
    }

    /**
     * Reporte R10: Panel de KPIs Ejecutivo
     * Muestra KPIs consolidados: turnaround, espera_camion, cumpl_citas, tramites_ok
     * Incluye comparativa con periodo anterior y tendencias
     */
    public function r10(Request $request): View
    {
        // Construir filtros desde la request
        $filters = [];

        if ($request->filled('fecha_desde')) {
            $filters['fecha_desde'] = $request->input('fecha_desde');
        }

        if ($request->filled('fecha_hasta')) {
            $filters['fecha_hasta'] = $request->input('fecha_hasta');
        }

        // Metas configurables
        if ($request->filled('meta_turnaround')) {
            $filters['meta_turnaround'] = (float) $request->input('meta_turnaround');
        }

        if ($request->filled('meta_espera_camion')) {
            $filters['meta_espera_camion'] = (float) $request->input('meta_espera_camion');
        }

        if ($request->filled('meta_cumpl_citas')) {
            $filters['meta_cumpl_citas'] = (float) $request->input('meta_cumpl_citas');
        }

        if ($request->filled('meta_tramites_ok')) {
            $filters['meta_tramites_ok'] = (float) $request->input('meta_tramites_ok');
        }

        // Generar reporte
        $report = $this->reportService->generateR10($filters);

        return view('reports.kpi.panel', [
            'kpis' => $report['kpis'],
            'periodo_actual' => $report['periodo_actual'],
            'periodo_anterior' => $report['periodo_anterior'],
            'filters' => $filters,
        ]);
    }

    /**
     * API endpoint para obtener datos de KPIs (para polling con Alpine.js)
     * Retorna JSON con los KPIs actualizados
     */
    public function r10Api(Request $request)
    {
        // Construir filtros desde la request
        $filters = [];

        if ($request->filled('fecha_desde')) {
            $filters['fecha_desde'] = $request->input('fecha_desde');
        }

        if ($request->filled('fecha_hasta')) {
            $filters['fecha_hasta'] = $request->input('fecha_hasta');
        }

        // Metas configurables
        if ($request->filled('meta_turnaround')) {
            $filters['meta_turnaround'] = (float) $request->input('meta_turnaround');
        }

        if ($request->filled('meta_espera_camion')) {
            $filters['meta_espera_camion'] = (float) $request->input('meta_espera_camion');
        }

        if ($request->filled('meta_cumpl_citas')) {
            $filters['meta_cumpl_citas'] = (float) $request->input('meta_cumpl_citas');
        }

        if ($request->filled('meta_tramites_ok')) {
            $filters['meta_tramites_ok'] = (float) $request->input('meta_tramites_ok');
        }

        // Generar reporte
        $report = $this->reportService->generateR10($filters);

        return response()->json([
            'kpis' => $report['kpis'],
            'periodo_actual' => $report['periodo_actual'],
            'periodo_anterior' => $report['periodo_anterior'],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Reporte R11: Alertas Tempranas
     * Detecta condiciones de riesgo operacional y genera alertas
     * Alertas: congestión de muelles (utilización > 85%), acumulación de camiones (espera > 4h promedio)
     */
    public function r11(Request $request): View
    {
        // Construir filtros desde la request
        $filters = [];

        if ($request->filled('fecha_desde')) {
            $filters['fecha_desde'] = $request->input('fecha_desde');
        }

        if ($request->filled('fecha_hasta')) {
            $filters['fecha_hasta'] = $request->input('fecha_hasta');
        }

        if ($request->filled('umbral_congestión')) {
            $filters['umbral_congestión'] = (float) $request->input('umbral_congestión');
        }

        if ($request->filled('umbral_acumulación')) {
            $filters['umbral_acumulación'] = (float) $request->input('umbral_acumulación');
        }

        // Generar reporte
        $report = $this->reportService->generateR11($filters);

        return view('reports.analytics.early-warning', [
            'alertas' => $report['alertas'],
            'kpis' => $report['kpis'],
            'estado_general' => $report['estado_general'],
            'filters' => $filters,
        ]);
    }

    /**
     * API endpoint para obtener alertas tempranas (para polling con Alpine.js)
     * Retorna JSON con las alertas actualizadas
     */
    public function r11Api(Request $request)
    {
        // Construir filtros desde la request
        $filters = [];

        if ($request->filled('fecha_desde')) {
            $filters['fecha_desde'] = $request->input('fecha_desde');
        }

        if ($request->filled('fecha_hasta')) {
            $filters['fecha_hasta'] = $request->input('fecha_hasta');
        }

        if ($request->filled('umbral_congestión')) {
            $filters['umbral_congestión'] = (float) $request->input('umbral_congestión');
        }

        if ($request->filled('umbral_acumulación')) {
            $filters['umbral_acumulación'] = (float) $request->input('umbral_acumulación');
        }

        // Generar reporte
        $report = $this->reportService->generateR11($filters);

        return response()->json([
            'alertas' => $report['alertas'],
            'kpis' => $report['kpis'],
            'estado_general' => $report['estado_general'],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Reporte R12: Cumplimiento de SLAs
     * Muestra el cumplimiento de SLAs por actor (empresa, entidad)
     * Calcula: pct_cumplimiento, incumplimientos, penalidades
     */
    public function r12(Request $request): View
    {
        // Construir filtros desde la request
        $filters = [];

        if ($request->filled('fecha_desde')) {
            $filters['fecha_desde'] = $request->input('fecha_desde');
        }

        if ($request->filled('fecha_hasta')) {
            $filters['fecha_hasta'] = $request->input('fecha_hasta');
        }

        // Generar reporte
        $report = $this->reportService->generateR12($filters);

        return view('reports.sla.compliance', [
            'data' => $report['data'],
            'kpis' => $report['kpis'],
            'por_actor' => $report['por_actor'],
            'filters' => $filters,
        ]);
    }
}
