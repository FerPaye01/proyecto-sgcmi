<?php

/**
 * Demo script to show R5 KPI calculations
 * 
 * This script demonstrates the calculation of the three KPIs for R5:
 * - pct_no_show: Percentage of no-show appointments
 * - pct_tarde: Percentage of late appointments (>15 min)
 * - desvio_medio_min: Average deviation in minutes
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\ReportService;

$reportService = new ReportService();

echo "=== R5 KPI Calculation Demo ===\n\n";

// Generate R5 report
$result = $reportService->generateR5([], null);

echo "KPIs Calculated:\n";
echo "----------------\n";
echo "pct_no_show: {$result['kpis']['pct_no_show']}%\n";
echo "pct_tarde: {$result['kpis']['pct_tarde']}%\n";
echo "desvio_medio_min: {$result['kpis']['desvio_medio_min']} minutes\n";
echo "total_citas: {$result['kpis']['total_citas']}\n\n";

echo "Classification Breakdown:\n";
echo "-------------------------\n";
$clasificaciones = $result['data']->groupBy('clasificacion');
foreach ($clasificaciones as $tipo => $citas) {
    echo "{$tipo}: {$citas->count()} appointments\n";
}

echo "\n=== Demo Complete ===\n";
