<?php

declare(strict_types=1);

/**
 * Simple test to verify KPI command updates values
 */

// Set up environment
putenv('APP_ENV=local');
putenv('DB_CONNECTION=pgsql');
putenv('DB_DATABASE=sgcmi');

// Load Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';

// Bootstrap
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Simple KPI Command Test\n";
echo str_repeat("=", 60) . "\n\n";

// Test 1: Verify KPI definitions exist
echo "Test 1: Verify KPI definitions exist\n";
echo "-------------------------------------------\n";

$kpiDefs = DB::table('analytics.kpi_definition')->get();
echo "Found " . $kpiDefs->count() . " KPI definitions\n";

foreach ($kpiDefs as $def) {
    echo "  - {$def->code}: {$def->name}\n";
}

if ($kpiDefs->count() >= 4) {
    echo "✓ PASSED: All required KPI definitions exist\n";
} else {
    echo "✗ FAILED: Missing KPI definitions\n";
}

echo "\n";

// Test 2: Run command and verify it creates KPI values
echo "Test 2: Run command and verify it creates KPI values\n";
echo "-------------------------------------------\n";

// Clear existing values for today
$today = now()->toDateString();
DB::table('analytics.kpi_value')->where('periodo', $today)->delete();

// Run command
echo "Running: php artisan kpi:calculate --period=today\n";
$exitCode = $kernel->call('kpi:calculate', ['--period' => 'today']);

echo "Exit code: {$exitCode}\n";

if ($exitCode === 0) {
    echo "✓ Command executed successfully\n";
} else {
    echo "✗ Command failed with exit code {$exitCode}\n";
}

echo "\n";

// Test 3: Verify KPI values were created
echo "Test 3: Verify KPI values were created\n";
echo "-------------------------------------------\n";

$kpiValues = DB::table('analytics.kpi_value')
    ->where('periodo', $today)
    ->get();

echo "Found " . $kpiValues->count() . " KPI values for today\n";

foreach ($kpiValues as $value) {
    $kpiDef = DB::table('analytics.kpi_definition')->find($value->kpi_id);
    echo "  - {$kpiDef->code}: {$value->valor} (meta: {$value->meta})\n";
}

if ($kpiValues->count() > 0) {
    echo "✓ PASSED: KPI values were created\n";
} else {
    echo "✗ FAILED: No KPI values were created\n";
}

echo "\n";

// Test 4: Verify command does not recalculate without --force
echo "Test 4: Verify command does not recalculate without --force\n";
echo "-------------------------------------------\n";

// Get current value
$currentValue = DB::table('analytics.kpi_value')
    ->where('periodo', $today)
    ->first();

if ($currentValue) {
    $originalValue = $currentValue->valor;
    echo "Current KPI value: {$originalValue}\n";
    
    // Run command again without --force
    $exitCode = $kernel->call('kpi:calculate', ['--period' => 'today']);
    
    // Check if value changed
    $newValue = DB::table('analytics.kpi_value')
        ->where('periodo', $today)
        ->first();
    
    if ($newValue->valor === $originalValue) {
        echo "✓ PASSED: KPI value was not recalculated (still {$originalValue})\n";
    } else {
        echo "✗ FAILED: KPI value changed from {$originalValue} to {$newValue->valor}\n";
    }
} else {
    echo "✗ FAILED: No KPI value found to test\n";
}

echo "\n";

// Test 5: Verify command recalculates with --force
echo "Test 5: Verify command recalculates with --force\n";
echo "-------------------------------------------\n";

// Manually set a different value
DB::table('analytics.kpi_value')
    ->where('periodo', $today)
    ->update(['valor' => 999.99]);

$beforeValue = DB::table('analytics.kpi_value')
    ->where('periodo', $today)
    ->first()->valor;

echo "Value before --force: {$beforeValue}\n";

// Run command with --force
$exitCode = $kernel->call('kpi:calculate', ['--period' => 'today', '--force' => true]);

$afterValue = DB::table('analytics.kpi_value')
    ->where('periodo', $today)
    ->first()->valor;

echo "Value after --force: {$afterValue}\n";

if ($afterValue !== $beforeValue) {
    echo "✓ PASSED: KPI value was recalculated with --force\n";
} else {
    echo "✗ FAILED: KPI value was not recalculated\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "Tests completed\n";
echo str_repeat("=", 60) . "\n";
