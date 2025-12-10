<?php

/**
 * Demo: Cálculo de KPIs para Reporte R7 - Estado de Trámites por Nave
 * 
 * Este script demuestra cómo se calculan los KPIs:
 * - pct_completos_pre_arribo: Porcentaje de trámites completados antes del arribo de la nave
 * - lead_time_h: Tiempo promedio de procesamiento de trámites en horas
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\ReportService;
use App\Models\Tramite;
use App\Models\VesselCall;
use App\Models\Vessel;
use App\Models\Berth;
use App\Models\Entidad;

echo "\n=== DEMO: Cálculo de KPIs R7 - Estado de Trámites por Nave ===\n\n";

// Crear instancia del servicio
$reportService = new ReportService();

// Verificar si hay datos en la base de datos
$tramitesCount = Tramite::count();
$vesselCallsCount = VesselCall::count();

echo "Datos en la base de datos:\n";
echo "- Trámites: {$tramitesCount}\n";
echo "- Llamadas de naves: {$vesselCallsCount}\n\n";

if ($tramitesCount === 0) {
    echo "⚠️  No hay trámites en la base de datos.\n";
    echo "   Ejecuta los seeders primero: php artisan db:seed\n\n";
    exit(1);
}

// Generar reporte R7 sin filtros
echo "Generando reporte R7...\n\n";
$result = $reportService->generateR7([]);

// Mostrar KPIs
echo "=== KPIs CALCULADOS ===\n\n";
$kpis = $result['kpis'];

echo "1. Porcentaje de trámites completados antes del arribo:\n";
echo "   pct_completos_pre_arribo = {$kpis['pct_completos_pre_arribo']}%\n";
echo "   (Trámites aprobados antes de que la nave arribe al puerto)\n\n";

echo "2. Tiempo promedio de procesamiento:\n";
echo "   lead_time_h = {$kpis['lead_time_h']} horas\n";
echo "   (Tiempo desde fecha_inicio hasta fecha_fin para trámites aprobados)\n\n";

echo "3. Resumen de estados:\n";
echo "   - Total trámites: {$kpis['total_tramites']}\n";
echo "   - Aprobados: {$kpis['aprobados']}\n";
echo "   - Pendientes: {$kpis['pendientes']}\n";
echo "   - Rechazados: {$kpis['rechazados']}\n\n";

// Mostrar agrupación por nave
echo "=== TRÁMITES POR NAVE ===\n\n";
$porNave = $result['por_nave'];

if ($porNave->isEmpty()) {
    echo "No hay trámites agrupados por nave.\n\n";
} else {
    foreach ($porNave as $nave) {
        echo "Nave: {$nave['vessel_name']} (Viaje: {$nave['viaje_id']})\n";
        echo "  ETA: " . ($nave['eta'] ? $nave['eta']->format('Y-m-d H:i') : 'N/A') . "\n";
        echo "  ATA: " . ($nave['ata'] ? $nave['ata']->format('Y-m-d H:i') : 'N/A') . "\n";
        echo "  Total trámites: {$nave['total_tramites']}\n";
        echo "  Aprobados: {$nave['aprobados']}\n";
        echo "  Pendientes: {$nave['pendientes']}\n";
        echo "  Rechazados: {$nave['rechazados']}\n";
        echo "  Completos pre-arribo: {$nave['completos_pre_arribo']}\n";
        echo "  % Completos: {$nave['pct_completos']}%\n";
        echo "  Bloquea operación: " . ($nave['bloquea_operacion'] ? 'SÍ ⚠️' : 'NO ✓') . "\n";
        echo "\n";
    }
}

// Mostrar algunos trámites de ejemplo
echo "=== EJEMPLOS DE TRÁMITES ===\n\n";
$tramites = $result['data']->take(5);

foreach ($tramites as $tramite) {
    echo "Trámite ID: {$tramite->id}\n";
    echo "  Ext ID: {$tramite->tramite_ext_id}\n";
    echo "  Estado: {$tramite->estado}\n";
    echo "  Régimen: {$tramite->regimen}\n";
    echo "  Fecha inicio: " . ($tramite->fecha_inicio ? $tramite->fecha_inicio->format('Y-m-d H:i') : 'N/A') . "\n";
    echo "  Fecha fin: " . ($tramite->fecha_fin ? $tramite->fecha_fin->format('Y-m-d H:i') : 'N/A') . "\n";
    echo "  Lead time: " . ($tramite->lead_time_h !== null ? "{$tramite->lead_time_h} horas" : 'N/A') . "\n";
    echo "  Bloquea operación: " . ($tramite->bloquea_operacion ? 'SÍ ⚠️' : 'NO ✓') . "\n";
    echo "\n";
}

// Ejemplo con filtros
echo "=== EJEMPLO CON FILTROS ===\n\n";
echo "Filtrando trámites aprobados...\n";
$resultFiltrado = $reportService->generateR7([
    'estado' => 'APROBADO',
]);

echo "Total trámites aprobados: {$resultFiltrado['kpis']['total_tramites']}\n";
echo "Lead time promedio: {$resultFiltrado['kpis']['lead_time_h']} horas\n\n";

echo "=== FÓRMULAS DE CÁLCULO ===\n\n";
echo "1. pct_completos_pre_arribo:\n";
echo "   = (Trámites APROBADOS con fecha_fin < vessel_call.ata) / Total trámites * 100\n\n";

echo "2. lead_time_h:\n";
echo "   = Promedio de (fecha_fin - fecha_inicio) en horas para trámites APROBADOS\n\n";

echo "3. bloquea_operacion:\n";
echo "   = TRUE si estado IN ('INICIADO', 'EN_REVISION', 'OBSERVADO')\n\n";

echo "=== DEMO COMPLETADO ===\n\n";
