<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ExportService;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class ExportServiceTest extends TestCase
{
    private ExportService $exportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exportService = new ExportService();
    }

    public function test_export_csv_returns_response_with_correct_headers(): void
    {
        $data = [
            ['Nave' => 'MSC AURORA', 'Viaje' => 'V001', 'Estado' => 'ATRACADA'],
            ['Nave' => 'MAERSK LINE', 'Viaje' => 'V002', 'Estado' => 'EN_TRANSITO'],
        ];
        $headers = ['Nave', 'Viaje', 'Estado'];
        $filename = 'test_export';

        $response = $this->exportService->exportCsv($data, $headers, $filename);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString($filename . '.csv', $response->headers->get('Content-Disposition'));
    }

    public function test_export_csv_contains_headers_and_data(): void
    {
        $data = [
            ['MSC AURORA', 'V001', 'ATRACADA'],
            ['MAERSK LINE', 'V002', 'EN_TRANSITO'],
        ];
        $headers = ['Nave', 'Viaje', 'Estado'];
        $filename = 'test_export';

        $response = $this->exportService->exportCsv($data, $headers, $filename);
        $content = $response->getContent();

        $this->assertIsString($content);
        $this->assertStringContainsString('Nave', $content);
        $this->assertStringContainsString('Viaje', $content);
        $this->assertStringContainsString('Estado', $content);
        $this->assertStringContainsString('MSC AURORA', $content);
        $this->assertStringContainsString('MAERSK LINE', $content);
    }

    public function test_export_xlsx_returns_streamed_response(): void
    {
        $data = [
            ['MSC AURORA', 'V001', 'ATRACADA'],
            ['MAERSK LINE', 'V002', 'EN_TRANSITO'],
        ];
        $headers = ['Nave', 'Viaje', 'Estado'];
        $filename = 'test_export';

        $response = $this->exportService->exportXlsx($data, $headers, $filename);

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('spreadsheetml.sheet', $response->headers->get('Content-Type'));
        $this->assertStringContainsString($filename . '.xlsx', $response->headers->get('Content-Disposition'));
    }

    public function test_export_pdf_returns_response(): void
    {
        $data = [
            ['MSC AURORA', 'V001', 'ATRACADA'],
            ['MAERSK LINE', 'V002', 'EN_TRANSITO'],
        ];
        $headers = ['Nave', 'Viaje', 'Estado'];
        $filename = 'test_export';
        $title = 'Reporte de Prueba';

        $response = $this->exportService->exportPdf($data, $headers, $filename, $title);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_anonymize_pii_masks_sensitive_fields(): void
    {
        $data = [
            ['placa' => 'ABC-123', 'company' => 'Transportes SA', 'tramite_ext_id' => 'CUS-2025-001'],
            ['placa' => 'XYZ-789', 'company' => 'Logística SAC', 'tramite_ext_id' => 'CUS-2025-002'],
        ];

        $anonymized = $this->exportService->anonymizePII($data);

        $this->assertEquals('AB*****', $anonymized[0]['placa']);
        $this->assertEquals('XY*****', $anonymized[1]['placa']);
        $this->assertEquals('CU**********', $anonymized[0]['tramite_ext_id']);
        $this->assertEquals('CU**********', $anonymized[1]['tramite_ext_id']);
        $this->assertEquals('Transportes SA', $anonymized[0]['company']);
        $this->assertEquals('Logística SAC', $anonymized[1]['company']);
    }

    public function test_anonymize_pii_handles_short_values(): void
    {
        $data = [
            ['placa' => 'AB', 'company' => 'Test'],
            ['placa' => 'X', 'company' => 'Test2'],
        ];

        $anonymized = $this->exportService->anonymizePII($data);

        $this->assertEquals('**', $anonymized[0]['placa']);
        $this->assertEquals('*', $anonymized[1]['placa']);
    }

    public function test_anonymize_pii_with_custom_fields(): void
    {
        $data = [
            ['email' => 'user@example.com', 'phone' => '987654321'],
        ];

        $anonymized = $this->exportService->anonymizePII($data, ['email', 'phone']);

        // user@example.com = 16 chars, mask from position 2 = 14 asterisks
        $this->assertEquals('us**************', $anonymized[0]['email']);
        // 987654321 = 9 chars, mask from position 2 = 7 asterisks
        $this->assertEquals('98*******', $anonymized[0]['phone']);
    }

    public function test_export_csv_handles_empty_data(): void
    {
        $data = [];
        $headers = ['Nave', 'Viaje', 'Estado'];
        $filename = 'empty_export';

        $response = $this->exportService->exportCsv($data, $headers, $filename);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertStringContainsString('Nave', $content);
    }

    public function test_export_xlsx_handles_empty_data(): void
    {
        $data = [];
        $headers = ['Nave', 'Viaje', 'Estado'];
        $filename = 'empty_export';

        $response = $this->exportService->exportXlsx($data, $headers, $filename);

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_export_csv_contains_correct_data_structure(): void
    {
        // Simular datos de reporte R1
        $data = [
            ['MSC AURORA', 'V001', '2025-01-15 08:00', '2025-01-15 08:30', '30', 'ATRACADA'],
            ['MAERSK LINE', 'V002', '2025-01-15 14:00', '2025-01-15 14:45', '45', 'EN_TRANSITO'],
            ['CMA CGM', 'V003', '2025-01-16 10:00', '2025-01-16 09:50', '-10', 'ATRACADA'],
        ];
        $headers = ['Nave', 'Viaje', 'ETA', 'ATA', 'Demora (min)', 'Estado'];
        $filename = 'reporte_r1';

        $response = $this->exportService->exportCsv($data, $headers, $filename);
        $content = $response->getContent();

        $this->assertIsString($content);

        // Verificar que contiene todos los headers
        foreach ($headers as $header) {
            $this->assertStringContainsString($header, $content);
        }

        // Verificar que contiene todos los datos
        $this->assertStringContainsString('MSC AURORA', $content);
        $this->assertStringContainsString('V001', $content);
        $this->assertStringContainsString('2025-01-15 08:00', $content);
        $this->assertStringContainsString('30', $content);

        $this->assertStringContainsString('MAERSK LINE', $content);
        $this->assertStringContainsString('V002', $content);
        $this->assertStringContainsString('45', $content);

        $this->assertStringContainsString('CMA CGM', $content);
        $this->assertStringContainsString('V003', $content);
        $this->assertStringContainsString('-10', $content);

        // Verificar estructura CSV (líneas separadas)
        $lines = explode("\n", trim($content));
        $this->assertGreaterThanOrEqual(4, count($lines)); // Header + 3 data rows
    }

    public function test_export_csv_preserves_data_types_and_formats(): void
    {
        $data = [
            ['100', '45.50', '2025-01-15', 'ACTIVO', ''],
            ['200', '78.25', '2025-01-16', 'INACTIVO', 'N/A'],
        ];
        $headers = ['ID', 'Monto', 'Fecha', 'Estado', 'Observaciones'];
        $filename = 'test_data_types';

        $response = $this->exportService->exportCsv($data, $headers, $filename);
        $content = $response->getContent();

        $this->assertIsString($content);

        // Verificar que los números se mantienen
        $this->assertStringContainsString('100', $content);
        $this->assertStringContainsString('45.50', $content);
        $this->assertStringContainsString('200', $content);
        $this->assertStringContainsString('78.25', $content);

        // Verificar que las fechas se mantienen
        $this->assertStringContainsString('2025-01-15', $content);
        $this->assertStringContainsString('2025-01-16', $content);

        // Verificar que los textos se mantienen
        $this->assertStringContainsString('ACTIVO', $content);
        $this->assertStringContainsString('INACTIVO', $content);
        $this->assertStringContainsString('N/A', $content);
    }

    public function test_export_csv_handles_special_characters(): void
    {
        $data = [
            ['Empresa "ABC"', 'Descripción: Test, con comas', 'Monto: $1,000.50'],
            ['Línea\nNueva', 'Texto con; punto y coma', 'Valor = 100%'],
        ];
        $headers = ['Campo1', 'Campo2', 'Campo3'];
        $filename = 'test_special_chars';

        $response = $this->exportService->exportCsv($data, $headers, $filename);
        $content = $response->getContent();

        $this->assertIsString($content);
        $this->assertNotEmpty($content);

        // Verificar que el CSV se genera sin errores
        $this->assertStringContainsString('Campo1', $content);
        $this->assertStringContainsString('Campo2', $content);
        $this->assertStringContainsString('Campo3', $content);
    }

    public function test_export_csv_row_count_matches_data(): void
    {
        $data = [
            ['Row1Col1', 'Row1Col2', 'Row1Col3'],
            ['Row2Col1', 'Row2Col2', 'Row2Col3'],
            ['Row3Col1', 'Row3Col2', 'Row3Col3'],
            ['Row4Col1', 'Row4Col2', 'Row4Col3'],
            ['Row5Col1', 'Row5Col2', 'Row5Col3'],
        ];
        $headers = ['Header1', 'Header2', 'Header3'];
        $filename = 'test_row_count';

        $response = $this->exportService->exportCsv($data, $headers, $filename);
        $content = $response->getContent();

        $this->assertIsString($content);

        // Contar líneas (header + data rows)
        $lines = array_filter(explode("\n", $content), fn($line) => !empty(trim($line)));
        $this->assertEquals(6, count($lines)); // 1 header + 5 data rows

        // Verificar que cada fila está presente
        for ($i = 1; $i <= 5; $i++) {
            $this->assertStringContainsString("Row{$i}Col1", $content);
            $this->assertStringContainsString("Row{$i}Col2", $content);
            $this->assertStringContainsString("Row{$i}Col3", $content);
        }
    }

    public function test_anonymize_pii_with_numeric_indices(): void
    {
        // Simular datos de exportación de reportes aduaneros (arrays indexados numéricamente)
        $data = [
            ['CUS-2025-001', 'IMPORTACION', 'APROBADO', 'Entidad A'],
            ['CUS-2025-002', 'EXPORTACION', 'EN_REVISION', 'Entidad B'],
            ['CUS-2025-003', 'TRANSITO', 'RECHAZADO', 'Entidad C'],
        ];

        // Anonimizar el primer campo (índice 0) que contiene tramite_ext_id
        $anonymized = $this->exportService->anonymizePII($data, ['0']);

        // Verificar que el tramite_ext_id está enmascarado
        $this->assertEquals('CU**********', $anonymized[0][0]);
        $this->assertEquals('CU**********', $anonymized[1][0]);
        $this->assertEquals('CU**********', $anonymized[2][0]);

        // Verificar que los demás campos no están enmascarados
        $this->assertEquals('IMPORTACION', $anonymized[0][1]);
        $this->assertEquals('APROBADO', $anonymized[0][2]);
        $this->assertEquals('Entidad A', $anonymized[0][3]);

        $this->assertEquals('EXPORTACION', $anonymized[1][1]);
        $this->assertEquals('EN_REVISION', $anonymized[1][2]);
        $this->assertEquals('Entidad B', $anonymized[1][3]);
    }

    public function test_anonymize_pii_with_string_numeric_indices(): void
    {
        // Probar con índices numéricos como strings
        $data = [
            ['ABC-123', 'Company A', 'CUS-2025-001'],
            ['XYZ-789', 'Company B', 'CUS-2025-002'],
        ];

        // Usar índices como strings (como vienen de los formularios)
        $anonymized = $this->exportService->anonymizePII($data, ['0', '2']);

        // Verificar que ambos campos están enmascarados
        $this->assertEquals('AB*****', $anonymized[0][0]);
        $this->assertEquals('CU**********', $anonymized[0][2]);
        $this->assertEquals('Company A', $anonymized[0][1]); // No enmascarado

        $this->assertEquals('XY*****', $anonymized[1][0]);
        $this->assertEquals('CU**********', $anonymized[1][2]);
        $this->assertEquals('Company B', $anonymized[1][1]); // No enmascarado
    }

    public function test_anonymize_pii_customs_report_r7_format(): void
    {
        // Simular formato exacto del reporte R7
        $data = [
            [
                'CUS-2025-001',
                'MSC AURORA',
                'V001',
                'IMPORTACION',
                '1234567890',
                'APROBADO',
                '2025-01-15 08:00',
                '2025-01-15 14:00',
                'SUNAT',
                '6.00',
                'No',
            ],
            [
                'CUS-2025-002',
                'MAERSK LINE',
                'V002',
                'EXPORTACION',
                '9876543210',
                'EN_REVISION',
                '2025-01-16 10:00',
                'N/A',
                'ADUANAS',
                'N/A',
                'Sí',
            ],
        ];

        // Anonimizar tramite_ext_id (índice 0)
        $anonymized = $this->exportService->anonymizePII($data, ['0']);

        // Verificar que tramite_ext_id está enmascarado
        $this->assertEquals('CU**********', $anonymized[0][0]);
        $this->assertEquals('CU**********', $anonymized[1][0]);

        // Verificar que otros campos permanecen intactos
        $this->assertEquals('MSC AURORA', $anonymized[0][1]);
        $this->assertEquals('IMPORTACION', $anonymized[0][3]);
        $this->assertEquals('APROBADO', $anonymized[0][5]);
        $this->assertEquals('SUNAT', $anonymized[0][8]);

        $this->assertEquals('MAERSK LINE', $anonymized[1][1]);
        $this->assertEquals('EXPORTACION', $anonymized[1][3]);
        $this->assertEquals('EN_REVISION', $anonymized[1][5]);
        $this->assertEquals('ADUANAS', $anonymized[1][8]);
    }

    public function test_anonymize_pii_does_not_mask_non_string_values(): void
    {
        $data = [
            ['CUS-2025-001', 123, null, true, 45.67],
            ['CUS-2025-002', 456, null, false, 89.12],
        ];

        $anonymized = $this->exportService->anonymizePII($data, ['0', '1', '2', '3', '4']);

        // Solo el primer campo (string) debe ser enmascarado
        $this->assertEquals('CU**********', $anonymized[0][0]);
        $this->assertEquals('CU**********', $anonymized[1][0]);

        // Los demás valores no-string deben permanecer sin cambios
        $this->assertEquals(123, $anonymized[0][1]);
        $this->assertNull($anonymized[0][2]);
        $this->assertTrue($anonymized[0][3]);
        $this->assertEquals(45.67, $anonymized[0][4]);

        $this->assertEquals(456, $anonymized[1][1]);
        $this->assertNull($anonymized[1][2]);
        $this->assertFalse($anonymized[1][3]);
        $this->assertEquals(89.12, $anonymized[1][4]);
    }
}
