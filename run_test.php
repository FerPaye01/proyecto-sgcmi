<?php

// Load Laravel bootstrap
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';

// Run the test
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$status = $kernel->call('test', [
    'test' => 'tests/Feature/CalculateKpiCommandTest.php',
    '--run' => true,
]);

exit($status);
