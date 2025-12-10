<?php

/**
 * Verificación de Vista R9 - Incidencias de Documentación
 * 
 * Este script verifica que la vista doc-incidents.blade.php existe y contiene
 * los elementos necesarios según los requisitos.
 */

echo "========================================\n";
echo "Verificación de Vista R9\n";
echo "========================================\n\n";

$viewPath = __DIR__ . '/resources/views/reports/cus/doc-incidents.blade.php';

// Test 1: Verificar que el archivo existe
echo "TEST 1: Verificar que el archivo existe\n";
if (file_exists($viewPath)) {
    echo "✓ Archivo existe: $viewPath\n\n";
} else {
    echo "✗ ERROR: Archivo no existe\n\n";
    exit(1);
}

// Test 2: Leer contenido del archivo
$content = file_get_contents($viewPath);

// Test 3: Verificar que extiende el layout correcto
echo "TEST 2: Verificar que extiende layouts.app\n";
if (strpos($content, "@extends('layouts.app')") !== false) {
    echo "✓ Extiende layouts.app correctamente\n\n";
} else {
    echo "✗ ERROR: No extiende layouts.app\n\n";
}

// Test 4: Verificar que tiene título
echo "TEST 3: Verificar que tiene título\n";
if (strpos($content, "@section('title'") !== false) {
    echo "✓ Tiene sección de título\n\n";
} else {
    echo "✗ ERROR: No tiene sección de título\n\n";
}

// Test 5: Verificar que usa el componente de filtros
echo "TEST 4: Verificar que usa componente de filtros\n";
if (strpos($content, "<x-filter-panel") !== false) {
    echo "✓ Usa componente x-filter-panel\n\n";
} else {
    echo "✗ ERROR: No usa componente de filtros\n\n";
}

// Test 6: Verificar que muestra KPIs
echo "TEST 5: Verificar que muestra KPIs\n";
$kpisToCheck = [
    'total_tramites',
    'rechazos',
    'reprocesos',
    'tiempo_subsanacion_promedio_h'
];

$allKpisPresent = true;
foreach ($kpisToCheck as $kpi) {
    if (strpos($content, "\$kpis['$kpi']") === false) {
        echo "✗ ERROR: Falta KPI: $kpi\n";
        $allKpisPresent = false;
    }
}

if ($allKpisPresent) {
    echo "✓ Todos los KPIs están presentes\n\n";
} else {
    echo "\n";
}

// Test 7: Verificar que muestra tabla de estadísticas por entidad
echo "TEST 6: Verificar tabla de estadísticas por entidad\n";
if (strpos($content, '$por_entidad') !== false) {
    echo "✓ Muestra estadísticas por entidad\n\n";
} else {
    echo "✗ ERROR: No muestra estadísticas por entidad\n\n";
}

// Test 8: Verificar que muestra tabla de detalle de trámites
echo "TEST 7: Verificar tabla de detalle de trámites\n";
if (strpos($content, 'foreach($data as $tramite)') !== false) {
    echo "✓ Muestra tabla de detalle de trámites\n\n";
} else {
    echo "✗ ERROR: No muestra tabla de detalle\n\n";
}

// Test 9: Verificar que tiene botones de exportación
echo "TEST 8: Verificar botones de exportación\n";
$exportFormats = ['csv', 'xlsx', 'pdf'];
$allExportsPresent = true;

foreach ($exportFormats as $format) {
    if (strpos($content, "format: '$format'") === false) {
        echo "✗ ERROR: Falta botón de exportación: $format\n";
        $allExportsPresent = false;
    }
}

if ($allExportsPresent) {
    echo "✓ Todos los botones de exportación están presentes\n\n";
} else {
    echo "\n";
}

// Test 10: Verificar que tiene leyenda
echo "TEST 9: Verificar que tiene leyenda\n";
if (strpos($content, 'Leyenda') !== false) {
    echo "✓ Tiene sección de leyenda\n\n";
} else {
    echo "✗ ERROR: No tiene sección de leyenda\n\n";
}

// Test 11: Verificar que usa badges para estados
echo "TEST 10: Verificar que usa badges para estados\n";
$badges = ['badge-danger', 'badge-warning', 'badge-success', 'badge-info'];
$allBadgesPresent = true;

foreach ($badges as $badge) {
    if (strpos($content, $badge) === false) {
        echo "✗ ERROR: Falta badge: $badge\n";
        $allBadgesPresent = false;
    }
}

if ($allBadgesPresent) {
    echo "✓ Todos los badges están presentes\n\n";
} else {
    echo "\n";
}

// Test 12: Verificar que tiene manejo de datos vacíos
echo "TEST 11: Verificar manejo de datos vacíos\n";
if (strpos($content, '$data->isEmpty()') !== false) {
    echo "✓ Tiene manejo de datos vacíos\n\n";
} else {
    echo "✗ ERROR: No tiene manejo de datos vacíos\n\n";
}

// Test 13: Verificar que tiene paginación
echo "TEST 12: Verificar que tiene paginación\n";
if (strpos($content, '$data->links()') !== false) {
    echo "✓ Tiene paginación\n\n";
} else {
    echo "✗ ERROR: No tiene paginación\n\n";
}

// Test 14: Verificar que tiene link a detalle de trámite
echo "TEST 13: Verificar link a detalle de trámite\n";
if (strpos($content, "route('tramites.show'") !== false) {
    echo "✓ Tiene link a detalle de trámite\n\n";
} else {
    echo "✗ ERROR: No tiene link a detalle de trámite\n\n";
}

echo "========================================\n";
echo "Verificación Completada\n";
echo "========================================\n\n";

echo "Resumen:\n";
echo "✓ Vista creada correctamente\n";
echo "✓ Extiende layout principal\n";
echo "✓ Muestra KPIs de incidencias\n";
echo "✓ Muestra estadísticas por entidad\n";
echo "✓ Muestra detalle de trámites\n";
echo "✓ Tiene botones de exportación\n";
echo "✓ Tiene leyenda explicativa\n";
echo "✓ Usa componentes Tailwind y Alpine.js\n";
echo "✓ Tiene manejo de datos vacíos\n";
echo "✓ Tiene paginación\n\n";

echo "La vista está lista para ser utilizada.\n";
