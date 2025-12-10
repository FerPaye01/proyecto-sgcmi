<?php

/**
 * Script de demostración para el Reporte R8: Tiempo de Despacho
 * 
 * Este script simula los datos que el controlador pasaría a la vista
 * para verificar que todos los elementos se renderizan correctamente.
 */

require __DIR__ . '/vendor/autoload.php';

echo "=== Demostración del Reporte R8: Tiempo de Despacho ===\n\n";

// Simular datos que el controlador pasaría a la vista
$demoData = [
    'kpis' => [
        'p50_horas' => 18.50,
        'p90_horas' => 36.75,
        'promedio_horas' => 22.30,
        'fuera_umbral_pct' => 15.25,
        'fuera_umbral' => 12,
        'total_tramites' => 78,
        'umbral_horas' => 24,
    ],
    
    'por_regimen' => [
        [
            'regimen' => 'IMPORTACION',
            'total' => 45,
            'p50_horas' => 20.00,
            'p90_horas' => 38.50,
            'promedio_horas' => 24.10,
            'fuera_umbral' => 8,
            'fuera_umbral_pct' => 17.78,
        ],
        [
            'regimen' => 'EXPORTACION',
            'total' => 28,
            'p50_horas' => 16.25,
            'p90_horas' => 32.00,
            'promedio_horas' => 19.50,
            'fuera_umbral' => 3,
            'fuera_umbral_pct' => 10.71,
        ],
        [
            'regimen' => 'TRANSITO',
            'total' => 5,
            'p50_horas' => 12.00,
            'p90_horas' => 22.50,
            'promedio_horas' => 14.80,
            'fuera_umbral' => 1,
            'fuera_umbral_pct' => 20.00,
        ],
    ],
    
    'filters' => [
        'fecha_desde' => '2025-01-01',
        'fecha_hasta' => '2025-01-31',
        'regimen' => '',
        'entidad_id' => '',
        'umbral_horas' => 24,
    ],
];

// Mostrar KPIs
echo "KPIs Globales:\n";
echo "==============\n";
echo "Percentil 50 (Mediana): " . number_format($demoData['kpis']['p50_horas'], 2) . " horas\n";
echo "Percentil 90:           " . number_format($demoData['kpis']['p90_horas'], 2) . " horas\n";
echo "Promedio:               " . number_format($demoData['kpis']['promedio_horas'], 2) . " horas\n";
echo "Fuera de Umbral:        " . number_format($demoData['kpis']['fuera_umbral_pct'], 2) . "% ";
echo "(" . $demoData['kpis']['fuera_umbral'] . " de " . $demoData['kpis']['total_tramites'] . " trámites)\n";
echo "Umbral Configurado:     " . $demoData['kpis']['umbral_horas'] . " horas\n";

// Mostrar resumen por régimen
echo "\nResumen por Régimen:\n";
echo "====================\n";
printf("%-15s | %6s | %8s | %8s | %10s | %15s | %10s\n", 
    "Régimen", "Total", "P50 (h)", "P90 (h)", "Prom (h)", "Fuera Umbral", "% Fuera"
);
echo str_repeat("-", 95) . "\n";

foreach ($demoData['por_regimen'] as $regimen) {
    $color = $regimen['fuera_umbral_pct'] > 20 ? '🔴' : 
             ($regimen['fuera_umbral_pct'] > 10 ? '🟡' : '🟢');
    
    printf("%-15s | %6d | %8.2f | %8.2f | %10.2f | %15d | %8.2f%% %s\n",
        $regimen['regimen'],
        $regimen['total'],
        $regimen['p50_horas'],
        $regimen['p90_horas'],
        $regimen['promedio_horas'],
        $regimen['fuera_umbral'],
        $regimen['fuera_umbral_pct'],
        $color
    );
}

// Análisis de resultados
echo "\nAnálisis de Resultados:\n";
echo "=======================\n";

// Identificar régimen más eficiente
$regimenMasRapido = null;
$menorP50 = PHP_FLOAT_MAX;
foreach ($demoData['por_regimen'] as $regimen) {
    if ($regimen['p50_horas'] < $menorP50) {
        $menorP50 = $regimen['p50_horas'];
        $regimenMasRapido = $regimen['regimen'];
    }
}
echo "✓ Régimen más eficiente: {$regimenMasRapido} (P50: " . number_format($menorP50, 2) . " horas)\n";

// Identificar régimen con más problemas
$regimenConProblemas = null;
$mayorPctFuera = 0;
foreach ($demoData['por_regimen'] as $regimen) {
    if ($regimen['fuera_umbral_pct'] > $mayorPctFuera) {
        $mayorPctFuera = $regimen['fuera_umbral_pct'];
        $regimenConProblemas = $regimen['regimen'];
    }
}
echo "⚠ Régimen con más incumplimientos: {$regimenConProblemas} (" . number_format($mayorPctFuera, 2) . "% fuera de umbral)\n";

// Evaluar cumplimiento general
$cumplimientoGeneral = 100 - $demoData['kpis']['fuera_umbral_pct'];
if ($cumplimientoGeneral >= 90) {
    echo "✓ Cumplimiento general: EXCELENTE (" . number_format($cumplimientoGeneral, 2) . "%)\n";
} elseif ($cumplimientoGeneral >= 80) {
    echo "✓ Cumplimiento general: BUENO (" . number_format($cumplimientoGeneral, 2) . "%)\n";
} elseif ($cumplimientoGeneral >= 70) {
    echo "⚠ Cumplimiento general: REGULAR (" . number_format($cumplimientoGeneral, 2) . "%)\n";
} else {
    echo "✗ Cumplimiento general: DEFICIENTE (" . number_format($cumplimientoGeneral, 2) . "%)\n";
}

// Comparar P50 vs P90
$diferenciaPercentiles = $demoData['kpis']['p90_horas'] - $demoData['kpis']['p50_horas'];
$ratioPercentiles = $demoData['kpis']['p90_horas'] / $demoData['kpis']['p50_horas'];
echo "\nVariabilidad de Tiempos:\n";
echo "  P90 - P50: " . number_format($diferenciaPercentiles, 2) . " horas\n";
echo "  Ratio P90/P50: " . number_format($ratioPercentiles, 2) . "x\n";
if ($ratioPercentiles > 2.0) {
    echo "  ⚠ Alta variabilidad detectada - revisar casos extremos\n";
} else {
    echo "  ✓ Variabilidad aceptable\n";
}

// Recomendaciones
echo "\nRecomendaciones:\n";
echo "================\n";

if ($demoData['kpis']['fuera_umbral_pct'] > 20) {
    echo "1. 🔴 CRÍTICO: Más del 20% de trámites exceden el umbral\n";
    echo "   - Revisar procesos de " . $regimenConProblemas . "\n";
    echo "   - Considerar aumentar recursos o mejorar procedimientos\n";
} elseif ($demoData['kpis']['fuera_umbral_pct'] > 10) {
    echo "1. 🟡 ATENCIÓN: Entre 10-20% de trámites exceden el umbral\n";
    echo "   - Monitorear de cerca el régimen " . $regimenConProblemas . "\n";
} else {
    echo "1. 🟢 BIEN: Menos del 10% de trámites exceden el umbral\n";
    echo "   - Mantener las buenas prácticas actuales\n";
}

if ($ratioPercentiles > 2.0) {
    echo "2. ⚠ Investigar casos que toman más del doble del tiempo mediano\n";
    echo "   - Identificar cuellos de botella en el proceso\n";
}

echo "\n=== Fin de la Demostración ===\n";
echo "\nPara ver el reporte en el navegador:\n";
echo "1. Inicia el servidor: php artisan serve\n";
echo "2. Accede a: http://localhost:8000/reports/cus/dispatch-time\n";
echo "3. Usa credenciales de un usuario con permiso CUS_REPORT_READ\n";
