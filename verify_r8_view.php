<?php

/**
 * Script de verificación para el Reporte R8: Tiempo de Despacho
 * 
 * Este script verifica que:
 * 1. La vista existe y está correctamente ubicada
 * 2. La ruta está configurada
 * 3. El controlador tiene el método r8
 * 4. El servicio tiene el método generateR8
 */

echo "=== Verificación del Reporte R8: Tiempo de Despacho ===\n\n";

// 1. Verificar que la vista existe
$viewPath = __DIR__ . '/resources/views/reports/cus/dispatch-time.blade.php';
if (file_exists($viewPath)) {
    echo "✓ Vista encontrada: {$viewPath}\n";
    $viewSize = filesize($viewPath);
    echo "  Tamaño: " . number_format($viewSize) . " bytes\n";
} else {
    echo "✗ Vista NO encontrada: {$viewPath}\n";
    exit(1);
}

// 2. Verificar que la ruta está configurada
$routesPath = __DIR__ . '/routes/web.php';
if (file_exists($routesPath)) {
    $routesContent = file_get_contents($routesPath);
    if (strpos($routesContent, "Route::get('/dispatch-time'") !== false) {
        echo "✓ Ruta configurada en routes/web.php\n";
        if (strpos($routesContent, "->name('reports.r8')") !== false) {
            echo "  Nombre de ruta: reports.r8\n";
        }
    } else {
        echo "✗ Ruta NO configurada en routes/web.php\n";
    }
}

// 3. Verificar que el controlador tiene el método r8
$controllerPath = __DIR__ . '/app/Http/Controllers/ReportController.php';
if (file_exists($controllerPath)) {
    $controllerContent = file_get_contents($controllerPath);
    if (strpos($controllerContent, 'public function r8(') !== false) {
        echo "✓ Método r8() encontrado en ReportController\n";
        if (strpos($controllerContent, "view('reports.cus.dispatch-time'") !== false) {
            echo "  Retorna la vista correcta\n";
        }
    } else {
        echo "✗ Método r8() NO encontrado en ReportController\n";
    }
}

// 4. Verificar que el servicio tiene el método generateR8
$servicePath = __DIR__ . '/app/Services/ReportService.php';
if (file_exists($servicePath)) {
    $serviceContent = file_get_contents($servicePath);
    if (strpos($serviceContent, 'public function generateR8(') !== false) {
        echo "✓ Método generateR8() encontrado en ReportService\n";
        
        // Verificar que calcula los KPIs correctos
        $expectedKpis = ['p50_horas', 'p90_horas', 'promedio_horas', 'fuera_umbral_pct'];
        $foundKpis = [];
        foreach ($expectedKpis as $kpi) {
            if (strpos($serviceContent, "'{$kpi}'") !== false) {
                $foundKpis[] = $kpi;
            }
        }
        echo "  KPIs implementados: " . implode(', ', $foundKpis) . "\n";
    } else {
        echo "✗ Método generateR8() NO encontrado en ReportService\n";
    }
}

// 5. Verificar estructura de la vista
echo "\n=== Análisis de la Vista ===\n";
$viewContent = file_get_contents($viewPath);

$sections = [
    'KPIs Section' => 'grid grid-cols-1 md:grid-cols-5',
    'Filters Section' => 'Filtros',
    'Export Buttons' => 'Exportar Reporte',
    'Resumen por Régimen' => 'Resumen por Régimen Aduanero',
    'Detalle de Trámites' => 'Detalle de Trámites Aprobados',
    'Ayuda' => 'Ayuda',
];

foreach ($sections as $name => $marker) {
    if (strpos($viewContent, $marker) !== false) {
        echo "✓ Sección encontrada: {$name}\n";
    } else {
        echo "✗ Sección NO encontrada: {$name}\n";
    }
}

// 6. Verificar que usa los datos correctos del controlador
echo "\n=== Verificación de Variables de Vista ===\n";
$requiredVars = [
    '$kpis' => ['p50_horas', 'p90_horas', 'promedio_horas', 'fuera_umbral_pct', 'umbral_horas'],
    '$data' => ['tramite_ext_id', 'tiempo_despacho_h'],
    '$por_regimen' => ['regimen', 'total', 'p50_horas', 'p90_horas'],
    '$filters' => ['fecha_desde', 'fecha_hasta', 'regimen', 'entidad_id', 'umbral_horas'],
    '$entidades' => ['name'],
    '$regimenes' => ['IMPORTACION', 'EXPORTACION', 'TRANSITO'],
];

foreach ($requiredVars as $var => $fields) {
    $varFound = strpos($viewContent, $var) !== false;
    if ($varFound) {
        echo "✓ Variable {$var} utilizada en la vista\n";
        $fieldsFound = 0;
        foreach ($fields as $field) {
            if (strpos($viewContent, $field) !== false) {
                $fieldsFound++;
            }
        }
        echo "  Campos encontrados: {$fieldsFound}/" . count($fields) . "\n";
    } else {
        echo "✗ Variable {$var} NO utilizada en la vista\n";
    }
}

// 7. Verificar permisos
echo "\n=== Verificación de Permisos ===\n";
if (strpos($routesContent, "middleware('permission:CUS_REPORT_READ')") !== false) {
    echo "✓ Permiso CUS_REPORT_READ requerido para acceder al reporte\n";
}
if (strpos($viewContent, "hasPermission('REPORT_EXPORT')") !== false) {
    echo "✓ Permiso REPORT_EXPORT verificado para exportaciones\n";
}

echo "\n=== Verificación Completada ===\n";
echo "La vista del Reporte R8 ha sido creada correctamente.\n";
echo "\nPara probar el reporte:\n";
echo "1. Asegúrate de que las migraciones estén ejecutadas\n";
echo "2. Asegúrate de tener datos de prueba en la tabla aduanas.tramite\n";
echo "3. Accede a: http://localhost:8000/reports/cus/dispatch-time\n";
echo "4. Inicia sesión con un usuario que tenga el permiso CUS_REPORT_READ\n";
