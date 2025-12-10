<?php

declare(strict_types=1);

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use League\Csv\Writer;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    /**
     * Exporta datos a formato CSV
     *
     * @param array<int, array<string, mixed>> $data
     * @param array<int, string> $headers
     * @param string $filename
     * @return Response
     */
    public function exportCsv(array $data, array $headers, string $filename): Response
    {
        $csv = Writer::createFromString('');
        
        // Insertar encabezados
        $csv->insertOne($headers);
        
        // Insertar datos
        $csv->insertAll($data);
        
        return response($csv->toString(), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$filename}.csv",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Exporta datos a formato XLSX (Excel)
     *
     * @param array<int, array<string, mixed>> $data
     * @param array<int, string> $headers
     * @param string $filename
     * @return StreamedResponse
     */
    public function exportXlsx(array $data, array $headers, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Insertar encabezados
        $sheet->fromArray($headers, null, 'A1');
        
        // Insertar datos
        if (!empty($data)) {
            $sheet->fromArray($data, null, 'A2');
        }
        
        // Aplicar estilo a encabezados
        $highestColumn = $sheet->getHighestColumn();
        $headerRange = 'A1:' . $highestColumn . '1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        
        // Auto-ajustar ancho de columnas
        foreach (range('A', $highestColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        $writer = new Xlsx($spreadsheet);
        
        return response()->streamDownload(
            function () use ($writer): void {
                $writer->save('php://output');
            },
            "{$filename}.xlsx",
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]
        );
    }

    /**
     * Exporta datos a formato PDF
     *
     * @param array<int, array<string, mixed>> $data
     * @param array<int, string> $headers
     * @param string $filename
     * @param string $title
     * @return Response
     */
    public function exportPdf(array $data, array $headers, string $filename, string $title): Response
    {
        $pdf = Pdf::loadView('reports.pdf-template', [
            'title' => $title,
            'headers' => $headers,
            'data' => $data,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ]);
        
        // Configurar orientación horizontal (landscape) para mejor visualización
        $pdf->setPaper('a4', 'landscape');
        
        return $pdf->download("{$filename}.pdf");
    }

    /**
     * Anonimiza datos sensibles (PII) en los datos de exportación
     * Enmascara campos como placa y tramite_ext_id
     * Soporta tanto claves de string (nombres de campos) como índices numéricos
     *
     * @param array<int, array<string, mixed>> $data
     * @param array<int, string|int> $piiFields
     * @return array<int, array<string, mixed>>
     */
    public function anonymizePII(array $data, array $piiFields = ['placa', 'tramite_ext_id']): array
    {
        return array_map(function ($row) use ($piiFields) {
            foreach ($piiFields as $field) {
                // Convertir índice string a int si es numérico
                $fieldKey = is_numeric($field) ? (int)$field : $field;
                
                if (isset($row[$fieldKey]) && is_string($row[$fieldKey])) {
                    $row[$fieldKey] = $this->maskValue($row[$fieldKey]);
                }
            }
            return $row;
        }, $data);
    }

    /**
     * Enmascara un valor sensible
     *
     * @param string $value
     * @return string
     */
    private function maskValue(string $value): string
    {
        $length = strlen($value);
        
        if ($length <= 2) {
            return str_repeat('*', $length);
        }
        
        // Mostrar primeros 2 caracteres y enmascarar el resto
        return substr($value, 0, 2) . str_repeat('*', $length - 2);
    }
}
