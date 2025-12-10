<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

// Load the Laravel application
$app = require __DIR__ . '/bootstrap/app.php';

// Get the container
$container = $app;

// Verify that the command exists
$artisan = $container->make(\Illuminate\Contracts\Console\Kernel::class);

// Check if the command is registered
$commands = $artisan->all();

$kpiCommandExists = false;
foreach ($commands as $name => $command) {
    if (strpos($name, 'kpi:calculate') !== false) {
        $kpiCommandExists = true;
        echo "✓ Command 'kpi:calculate' found\n";
        break;
    }
}

if (!$kpiCommandExists) {
    echo "✗ Command 'kpi:calculate' NOT found\n";
    exit(1);
}

// Verify that the KpiCalculator service exists
try {
    $calculator = $container->make(\App\Services\KpiCalculator::class);
    echo "✓ KpiCalculator service loaded\n";
} catch (\Exception $e) {
    echo "✗ KpiCalculator service NOT found: " . $e->getMessage() . "\n";
    exit(1);
}

// Verify that the models exist
$models = [
    \App\Models\KpiDefinition::class,
    \App\Models\KpiValue::class,
    \App\Models\VesselCall::class,
    \App\Models\Appointment::class,
    \App\Models\Tramite::class,
];

foreach ($models as $model) {
    try {
        $instance = new $model();
        echo "✓ Model " . class_basename($model) . " loaded\n";
    } catch (\Exception $e) {
        echo "✗ Model " . class_basename($model) . " NOT found: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// Verify that the KpiCalculator methods exist
$methods = [
    'calculateTurnaround',
    'calculateWaitingTime',
    'calculateAppointmentCompliance',
    'calculateCustomsLeadTime',
];

foreach ($methods as $method) {
    if (method_exists($calculator, $method)) {
        echo "✓ KpiCalculator::{$method}() exists\n";
    } else {
        echo "✗ KpiCalculator::{$method}() NOT found\n";
        exit(1);
    }
}

echo "\n✓ All verifications passed!\n";
echo "The kpi:calculate command is properly configured and ready to use.\n";

exit(0);
