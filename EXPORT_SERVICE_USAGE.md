# ExportService - Guía de Uso

## Descripción

El `ExportService` proporciona métodos para exportar datos a diferentes formatos: CSV, XLSX (Excel) y PDF.

## Características

- ✅ Exportación a CSV con codificación UTF-8
- ✅ Exportación a XLSX con formato y estilos
- ✅ Exportación a PDF con plantilla personalizada
- ✅ Anonimización de datos sensibles (PII)
- ✅ Manejo de datos vacíos
- ✅ Headers HTTP apropiados para descarga

## Uso Básico

### 1. Exportar a CSV

```php
use App\Services\ExportService;

$exportService = new ExportService();

$data = [
    ['MSC AURORA', 'V001', 'ATRACADA'],
    ['MAERSK LINE', 'V002', 'EN_TRANSITO'],
];

$headers = ['Nave', 'Viaje', 'Estado'];
$filename = 'reporte_naves_' . date('Y-m-d');

return $exportService->exportCsv($data, $headers, $filename);
```

### 2. Exportar a XLSX (Excel)

```php
$exportService = new ExportService();

$data = [
    ['MSC AURORA', 'V001', 'ATRACADA', '2025-10-21 08:00'],
    ['MAERSK LINE', 'V002', 'EN_TRANSITO', '2025-10-22 10:00'],
];

$headers = ['Nave', 'Viaje', 'Estado', 'ETA'];
$filename = 'reporte_naves_' . date('Y-m-d');

return $exportService->exportXlsx($data, $headers, $filename);
```

### 3. Exportar a PDF

```php
$exportService = new ExportService();

$data = [
    ['MSC AURORA', 'V001', 'ATRACADA', '2025-10-21 08:00'],
    ['MAERSK LINE', 'V002', 'EN_TRANSITO', '2025-10-22 10:00'],
];

$headers = ['Nave', 'Viaje', 'Estado', 'ETA'];
$filename = 'reporte_naves_' . date('Y-m-d');
$title = 'Reporte de Llamadas de Naves';

return $exportService->exportPdf($data, $headers, $filename, $title);
```

## Anonimización de Datos (PII)

Para proteger información sensible, use el método `anonymizePII()`:

```php
$exportService = new ExportService();

$data = [
    ['placa' => 'ABC-123', 'company' => 'Transportes SA', 'tramite_ext_id' => 'CUS-2025-001'],
    ['placa' => 'XYZ-789', 'company' => 'Logística SAC', 'tramite_ext_id' => 'CUS-2025-002'],
];

// Anonimizar campos por defecto: placa, tramite_ext_id
$anonymized = $exportService->anonymizePII($data);

// Resultado:
// [
//     ['placa' => 'AB*****', 'company' => 'Transportes SA', 'tramite_ext_id' => 'CU**********'],
//     ['placa' => 'XY*****', 'company' => 'Logística SAC', 'tramite_ext_id' => 'CU**********'],
// ]

// Anonimizar campos personalizados
$anonymized = $exportService->anonymizePII($data, ['placa', 'company']);
```

## Ejemplo Completo en un Controlador

```php
namespace App\Http\Controllers;

use App\Services\ExportService;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function __construct(
        private ExportService $exportService,
        private ReportService $reportService
    ) {}

    public function exportR1(Request $request)
    {
        // Validar permiso
        if (!auth()->user()->hasPermission('REPORT_EXPORT')) {
            abort(403, 'No tiene permiso para exportar reportes');
        }

        // Obtener datos del reporte
        $filters = $request->only(['fecha_desde', 'fecha_hasta', 'berth_id', 'vessel_id']);
        $report = $this->reportService->generateR1($filters);

        // Preparar datos para exportación
        $data = $report['data']->map(function ($vc) {
            return [
                $vc->vessel->name,
                $vc->viaje_id,
                $vc->berth->name,
                $vc->eta?->format('Y-m-d H:i'),
                $vc->ata?->format('Y-m-d H:i'),
                $vc->etb?->format('Y-m-d H:i'),
                $vc->atb?->format('Y-m-d H:i'),
                $vc->atd?->format('Y-m-d H:i'),
                $vc->estado_llamada,
            ];
        })->toArray();

        $headers = ['Nave', 'Viaje', 'Muelle', 'ETA', 'ATA', 'ETB', 'ATB', 'ATD', 'Estado'];
        $filename = 'reporte_r1_' . date('Y-m-d_His');

        // Exportar según formato solicitado
        $format = $request->input('format', 'csv');

        return match ($format) {
            'xlsx' => $this->exportService->exportXlsx($data, $headers, $filename),
            'pdf' => $this->exportService->exportPdf($data, $headers, $filename, 'Reporte R1: Programación vs Ejecución'),
            default => $this->exportService->exportCsv($data, $headers, $filename),
        };
    }
}
```

## Formato de Datos

### Estructura de `$data`

El parámetro `$data` debe ser un array de arrays, donde cada sub-array representa una fila:

```php
$data = [
    ['valor1', 'valor2', 'valor3'],  // Fila 1
    ['valor4', 'valor5', 'valor6'],  // Fila 2
];
```

### Estructura de `$headers`

El parámetro `$headers` debe ser un array simple con los nombres de las columnas:

```php
$headers = ['Columna 1', 'Columna 2', 'Columna 3'];
```

## Plantilla PDF

La plantilla PDF se encuentra en `resources/views/reports/pdf-template.blade.php` y puede ser personalizada según necesidades.

Características de la plantilla:
- Orientación horizontal (landscape) para mejor visualización
- Encabezado con título y subtítulo
- Tabla con estilos alternados
- Pie de página con información del sistema
- Manejo de datos vacíos

## Tests

El servicio incluye tests unitarios completos en `tests/Unit/ExportServiceTest.php`:

- ✅ Exportación CSV con headers correctos
- ✅ Exportación XLSX con formato
- ✅ Exportación PDF
- ✅ Anonimización de PII
- ✅ Manejo de datos vacíos
- ✅ Campos personalizados para anonimización

Ejecutar tests:
```bash
php artisan test --filter=ExportServiceTest
```

## Seguridad

- **PII Protection**: Los campos sensibles (`placa`, `tramite_ext_id`) se enmascaran automáticamente
- **Rate Limiting**: Aplicar throttle en rutas de exportación (5/minuto según steering rules)
- **Permissions**: Validar permiso `REPORT_EXPORT` antes de permitir exportaciones
- **Cache Control**: Headers HTTP configurados para evitar caché de archivos sensibles

## Próximos Pasos

Para integrar el ExportService en el sistema:

1. Crear `ExportController` con método `export()`
2. Agregar rutas de exportación en `routes/web.php`
3. Implementar botones de exportación en vistas de reportes
4. Aplicar rate limiting en rutas de exportación
5. Verificar permisos `REPORT_EXPORT` en cada endpoint
