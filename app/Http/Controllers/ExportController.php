<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AuditService;
use App\Services\ExportService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(
        private readonly ExportService $exportService,
        private readonly ReportService $reportService,
        private readonly AuditService $auditService
    ) {
    }

    /**
     * Exporta un reporte en el formato solicitado
     * 
     * @param string $report Tipo de reporte (r1, r2, r3, etc.)
     * @param Request $request Contiene formato (csv, xlsx, pdf) y filtros
     */
    public function export(string $report, Request $request): Response|StreamedResponse
    {
        // Validar permiso
        if (!auth()->user()->hasPermission('REPORT_EXPORT')) {
            abort(403, 'No tiene permiso para exportar reportes');
        }

        // Validar formato
        $format = $request->input('format', 'csv');
        if (!in_array($format, ['csv', 'xlsx', 'pdf'])) {
            abort(400, 'Formato no válido. Use: csv, xlsx o pdf');
        }

        // Obtener filtros del request
        $filters = $request->except(['format', '_token']);

        // Generar reporte según tipo
        $reportData = match ($report) {
            'r1' => $this->generateR1Export($filters),
            'r3' => $this->generateR3Export($filters),
            'r4' => $this->generateR4Export($filters),
            'r5' => $this->generateR5Export($filters),
            'r6' => $this->generateR6Export($filters),
            'r7' => $this->generateR7Export($filters),
            'r8' => $this->generateR8Export($filters),
            'r9' => $this->generateR9Export($filters),
            'r10' => $this->generateR10Export($filters),
            'r11' => $this->generateR11Export($filters),
            'r12' => $this->generateR12Export($filters),
            default => abort(404, "Reporte '{$report}' no encontrado"),
        };

        // Registrar auditoría
        $this->auditService->log(
            action: 'EXPORT',
            objectSchema: 'reports',
            objectTable: $reportData['table'],
            objectId: null,
            details: [
                'report' => $report,
                'format' => $format,
                'filters' => $filters,
                'record_count' => count($reportData['data']),
            ]
        );

        // Generar nombre de archivo
        $filename = "reporte_{$report}_" . date('Y-m-d_His');

        // Exportar según formato
        return match ($format) {
            'xlsx' => $this->exportService->exportXlsx(
                $reportData['data'],
                $reportData['headers'],
                $filename
            ),
            'pdf' => $this->exportService->exportPdf(
                $reportData['data'],
                $reportData['headers'],
                $filename,
                $reportData['title']
            ),
            default => $this->exportService->exportCsv(
                $reportData['data'],
                $reportData['headers'],
                $filename
            ),
        };
    }

    /**
     * Genera datos de exportación para el reporte R1
     * 
     * @param array<string, mixed> $filters
     * @return array{data: array, headers: array, title: string, table: string}
     */
    private function generateR1Export(array $filters): array
    {
        $report = $this->reportService->generateR1($filters);

        $data = $report['data']->map(function ($vc) {
            return [
                $vc->vessel->name ?? 'N/A',
                $vc->viaje_id ?? 'N/A',
                $vc->berth->name ?? 'N/A',
                $vc->eta?->format('Y-m-d H:i') ?? 'N/A',
                $vc->ata?->format('Y-m-d H:i') ?? 'N/A',
                $vc->etb?->format('Y-m-d H:i') ?? 'N/A',
                $vc->atb?->format('Y-m-d H:i') ?? 'N/A',
                $vc->atd?->format('Y-m-d H:i') ?? 'N/A',
                $vc->estado_llamada ?? 'N/A',
            ];
        })->toArray();

        return [
            'data' => $data,
            'headers' => ['Nave', 'Viaje', 'Muelle', 'ETA', 'ATA', 'ETB', 'ATB', 'ATD', 'Estado'],
            'title' => 'Reporte R1: Programación vs Ejecución',
            'table' => 'r1_schedule_vs_actual',
        ];
    }

    /**
     * Exporta el reporte R1 en el formato solicitado
     * @deprecated Use export('r1', $request) instead
     */
    public function exportR1(Request $request): Response|StreamedResponse
    {
        return $this->export('r1', $request);
    }

    /**
     * Genera datos de exportación para el reporte R7 (Estado de Trámites por Nave)
     * Aplica anonimización de PII (tramite_ext_id)
     * 
     * @param array<string, mixed> $filters
     * @return array{data: array, headers: array, title: string, table: string}
     */
    private function generateR7Export(array $filters): array
    {
        $report = $this->reportService->generateR7($filters);

        $data = $report['data']->map(function ($tramite) {
            return [
                $tramite->tramite_ext_id ?? 'N/A',
                $tramite->vesselCall->vessel->name ?? 'N/A',
                $tramite->vesselCall->viaje_id ?? 'N/A',
                $tramite->regimen ?? 'N/A',
                $tramite->subpartida ?? 'N/A',
                $tramite->estado ?? 'N/A',
                $tramite->fecha_inicio?->format('Y-m-d H:i') ?? 'N/A',
                $tramite->fecha_fin?->format('Y-m-d H:i') ?? 'N/A',
                $tramite->entidad->name ?? 'N/A',
                $tramite->lead_time_h !== null ? number_format($tramite->lead_time_h, 2) : 'N/A',
                $tramite->bloquea_operacion ? 'Sí' : 'No',
            ];
        })->toArray();

        // Aplicar anonimización de PII (tramite_ext_id está en la primera columna)
        $data = $this->exportService->anonymizePII($data, ['0']); // Índice 0 = tramite_ext_id

        return [
            'data' => $data,
            'headers' => [
                'Trámite ID',
                'Nave',
                'Viaje',
                'Régimen',
                'Subpartida',
                'Estado',
                'Fecha Inicio',
                'Fecha Fin',
                'Entidad',
                'Lead Time (h)',
                'Bloquea Operación',
            ],
            'title' => 'Reporte R7: Estado de Trámites por Nave',
            'table' => 'r7_status_by_vessel',
        ];
    }

    /**
     * Genera datos de exportación para el reporte R8 (Tiempo de Despacho)
     * Aplica anonimización de PII (tramite_ext_id)
     * 
     * @param array<string, mixed> $filters
     * @return array{data: array, headers: array, title: string, table: string}
     */
    private function generateR8Export(array $filters): array
    {
        $report = $this->reportService->generateR8($filters);

        $data = $report['data']->map(function ($tramite) {
            return [
                $tramite->tramite_ext_id ?? 'N/A',
                $tramite->regimen ?? 'N/A',
                $tramite->subpartida ?? 'N/A',
                $tramite->entidad->name ?? 'N/A',
                $tramite->fecha_inicio?->format('Y-m-d H:i') ?? 'N/A',
                $tramite->fecha_fin?->format('Y-m-d H:i') ?? 'N/A',
                $tramite->tiempo_despacho_h !== null ? number_format($tramite->tiempo_despacho_h, 2) : 'N/A',
                $tramite->vesselCall->vessel->name ?? 'N/A',
                $tramite->vesselCall->viaje_id ?? 'N/A',
            ];
        })->toArray();

        // Aplicar anonimización de PII (tramite_ext_id está en la primera columna)
        $data = $this->exportService->anonymizePII($data, ['0']); // Índice 0 = tramite_ext_id

        return [
            'data' => $data,
            'headers' => [
                'Trámite ID',
                'Régimen',
                'Subpartida',
                'Entidad',
                'Fecha Inicio',
                'Fecha Fin',
                'Tiempo Despacho (h)',
                'Nave',
                'Viaje',
            ],
            'title' => 'Reporte R8: Tiempo de Despacho por Régimen',
            'table' => 'r8_dispatch_time',
        ];
    }

    /**
     * Genera datos de exportación para el reporte R9 (Incidencias de Documentación)
     * Aplica anonimización de PII (tramite_ext_id)
     * 
     * @param array<string, mixed> $filters
     * @return array{data: array, headers: array, title: string, table: string}
     */
    private function generateR9Export(array $filters): array
    {
        $report = $this->reportService->generateR9($filters);

        $data = $report['data']->map(function ($tramite) {
            return [
                $tramite->tramite_ext_id ?? 'N/A',
                $tramite->regimen ?? 'N/A',
                $tramite->estado ?? 'N/A',
                $tramite->entidad->name ?? 'N/A',
                $tramite->tiene_rechazo ? 'Sí' : 'No',
                $tramite->tiene_reproceso ? 'Sí' : 'No',
                $tramite->num_observaciones ?? 0,
                $tramite->tiempo_subsanacion_h !== null ? number_format($tramite->tiempo_subsanacion_h, 2) : 'N/A',
                $tramite->vesselCall->vessel->name ?? 'N/A',
                $tramite->vesselCall->viaje_id ?? 'N/A',
            ];
        })->toArray();

        // Aplicar anonimización de PII (tramite_ext_id está en la primera columna)
        $data = $this->exportService->anonymizePII($data, ['0']); // Índice 0 = tramite_ext_id

        return [
            'data' => $data,
            'headers' => [
                'Trámite ID',
                'Régimen',
                'Estado',
                'Entidad',
                'Tiene Rechazo',
                'Tiene Reproceso',
                'Num. Observaciones',
                'Tiempo Subsanación (h)',
                'Nave',
                'Viaje',
            ],
            'title' => 'Reporte R9: Incidencias de Documentación',
            'table' => 'r9_doc_incidents',
        ];
    }

    /**
     * Genera datos de exportación para el reporte R3 (Utilización de Muelles)
     */
    private function generateR3Export(array $filters): array
    {
        $report = $this->reportService->generateR3($filters);

        $data = collect($report['por_muelle'])->map(function ($item) {
            return [
                $item['berth_name'],
                number_format($item['utilizacion_pct'], 2) . '%',
                $item['horas_ocupadas'],
                $item['horas_disponibles'],
                $item['num_llamadas'],
            ];
        })->toArray();

        return [
            'data' => $data,
            'headers' => ['Muelle', 'Utilización %', 'Horas Ocupadas', 'Horas Disponibles', 'Num. Llamadas'],
            'title' => 'Reporte R3: Utilización de Muelles',
            'table' => 'r3_berth_utilization',
        ];
    }

    /**
     * Genera datos de exportación para el reporte R4 (Tiempo de Espera)
     */
    private function generateR4Export(array $filters): array
    {
        $user = auth()->user();
        $report = $this->reportService->generateR4($filters, $user);

        $data = $report['data']->map(function ($appt) {
            return [
                $appt->truck->placa ?? 'N/A',
                $appt->company->name ?? 'N/A',
                $appt->hora_programada?->format('Y-m-d H:i') ?? 'N/A',
                $appt->hora_llegada?->format('Y-m-d H:i') ?? 'N/A',
                $appt->tiempo_espera_min !== null ? number_format($appt->tiempo_espera_min, 0) : 'N/A',
                $appt->vesselCall->vessel->name ?? 'N/A',
            ];
        })->toArray();

        // Aplicar anonimización de PII (placa está en la primera columna)
        $data = $this->exportService->anonymizePII($data, ['0']);

        return [
            'data' => $data,
            'headers' => ['Placa', 'Empresa', 'Hora Programada', 'Hora Llegada', 'Tiempo Espera (min)', 'Nave'],
            'title' => 'Reporte R4: Tiempo de Espera de Camiones',
            'table' => 'r4_waiting_time',
        ];
    }

    /**
     * Genera datos de exportación para el reporte R5 (Cumplimiento de Citas)
     */
    private function generateR5Export(array $filters): array
    {
        $user = auth()->user();
        $report = $this->reportService->generateR5($filters, $user);

        $data = $report['data']->map(function ($appt) {
            return [
                $appt->truck->placa ?? 'N/A',
                $appt->company->name ?? 'N/A',
                $appt->hora_programada?->format('Y-m-d H:i') ?? 'N/A',
                $appt->hora_llegada?->format('Y-m-d H:i') ?? 'N/A',
                $appt->clasificacion ?? 'N/A',
                $appt->vesselCall->vessel->name ?? 'N/A',
            ];
        })->toArray();

        // Aplicar anonimización de PII (placa está en la primera columna)
        $data = $this->exportService->anonymizePII($data, ['0']);

        return [
            'data' => $data,
            'headers' => ['Placa', 'Empresa', 'Hora Programada', 'Hora Llegada', 'Clasificación', 'Nave'],
            'title' => 'Reporte R5: Cumplimiento de Citas',
            'table' => 'r5_appointments_compliance',
        ];
    }

    /**
     * Genera datos de exportación para el reporte R6 (Productividad de Gates)
     */
    private function generateR6Export(array $filters): array
    {
        $report = $this->reportService->generateR6($filters);

        $data = collect($report['por_gate'])->map(function ($item) {
            return [
                $item['gate_name'],
                $item['total_eventos'],
                number_format($item['vehiculos_por_hora'], 2),
                number_format($item['tiempo_ciclo_promedio_min'], 2),
            ];
        })->toArray();

        return [
            'data' => $data,
            'headers' => ['Gate', 'Total Eventos', 'Vehículos/Hora', 'Tiempo Ciclo Promedio (min)'],
            'title' => 'Reporte R6: Productividad de Gates',
            'table' => 'r6_gate_productivity',
        ];
    }

    /**
     * Genera datos de exportación para el reporte R10 (Panel de KPIs)
     */
    private function generateR10Export(array $filters): array
    {
        $report = $this->reportService->generateR10($filters);

        $data = [
            ['Turnaround Promedio (h)', number_format($report['kpis']['turnaround_promedio_h'] ?? 0, 2)],
            ['Utilización Muelles (%)', number_format($report['kpis']['utilizacion_muelles_pct'] ?? 0, 2)],
            ['Tiempo Espera Promedio (min)', number_format($report['kpis']['tiempo_espera_promedio_min'] ?? 0, 2)],
            ['Cumplimiento Citas (%)', number_format($report['kpis']['cumplimiento_citas_pct'] ?? 0, 2)],
            ['Productividad Gates (veh/h)', number_format($report['kpis']['productividad_gates_veh_h'] ?? 0, 2)],
            ['Tiempo Despacho Promedio (h)', number_format($report['kpis']['tiempo_despacho_promedio_h'] ?? 0, 2)],
        ];

        return [
            'data' => $data,
            'headers' => ['KPI', 'Valor'],
            'title' => 'Reporte R10: Panel de KPIs',
            'table' => 'r10_kpi_panel',
        ];
    }

    /**
     * Genera datos de exportación para el reporte R11 (Early Warning)
     */
    private function generateR11Export(array $filters): array
    {
        $report = $this->reportService->generateR11($filters);

        $data = collect($report['alertas'])->map(function ($alerta) {
            return [
                $alerta['tipo'],
                $alerta['severidad'],
                $alerta['descripcion'],
                $alerta['entidad'],
                $alerta['valor_actual'],
                $alerta['umbral'],
                $alerta['timestamp'],
            ];
        })->toArray();

        return [
            'data' => $data,
            'headers' => ['Tipo', 'Severidad', 'Descripción', 'Entidad', 'Valor Actual', 'Umbral', 'Timestamp'],
            'title' => 'Reporte R11: Alertas Tempranas (Early Warning)',
            'table' => 'r11_early_warning',
        ];
    }

    /**
     * Genera datos de exportación para el reporte R12 (Cumplimiento SLAs)
     */
    private function generateR12Export(array $filters): array
    {
        $report = $this->reportService->generateR12($filters);

        $data = collect($report['por_actor'])->map(function ($item) {
            return [
                $item['actor'],
                $item['total_operaciones'],
                $item['cumplidas'],
                $item['incumplidas'],
                number_format($item['cumplimiento_pct'], 2) . '%',
                number_format($item['penalidad_total'], 2),
            ];
        })->toArray();

        return [
            'data' => $data,
            'headers' => ['Actor', 'Total Operaciones', 'Cumplidas', 'Incumplidas', 'Cumplimiento %', 'Penalidad Total'],
            'title' => 'Reporte R12: Cumplimiento de SLAs',
            'table' => 'r12_sla_compliance',
        ];
    }
}

