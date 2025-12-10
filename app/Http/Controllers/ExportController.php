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
            'r7' => $this->generateR7Export($filters),
            'r8' => $this->generateR8Export($filters),
            'r9' => $this->generateR9Export($filters),
            // Futuros reportes se agregarán aquí
            // 'r2' => $this->generateR2Export($filters),
            // 'r3' => $this->generateR3Export($filters),
            // 'r4' => $this->generateR4Export($filters),
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
}

