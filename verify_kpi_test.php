<?php

declare(strict_types=1);

// Set up environment for testing
$_ENV['APP_ENV'] = 'testing';
$_ENV['APP_MAINTENANCE_DRIVER'] = 'file';
$_ENV['BCRYPT_ROUNDS'] = '4';
$_ENV['CACHE_STORE'] = 'array';
$_ENV['DB_CONNECTION'] = 'pgsql';
$_ENV['DB_DATABASE'] = 'sgcmi_test';
$_ENV['MAIL_MAILER'] = 'array';
$_ENV['PULSE_ENABLED'] = 'false';
$_ENV['QUEUE_CONNECTION'] = 'sync';
$_ENV['SESSION_DRIVER'] = 'array';
$_ENV['TELESCOPE_ENABLED'] = 'false';

// Load Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';

// Bootstrap the application
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Create test database if needed
$connection = \Illuminate\Support\Facades\DB::connection();
try {
    $connection->statement('SELECT 1');
    echo "✓ Database connection successful\n";
} catch (\Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Run migrations for test database
echo "\nRunning migrations for test database...\n";
$exitCode = $kernel->call('migrate', [
    '--database' => 'pgsql',
    '--env' => 'testing',
    '--force' => true,
]);

if ($exitCode !== 0) {
    echo "✗ Migrations failed\n";
    exit(1);
}

echo "✓ Migrations completed\n";

// Seed the database
echo "\nSeeding database...\n";
$exitCode = $kernel->call('db:seed', [
    '--class' => 'Database\\Seeders\\RolePermissionSeeder',
    '--force' => true,
]);

if ($exitCode !== 0) {
    echo "✗ Seeding failed\n";
    exit(1);
}

echo "✓ Database seeded\n";

// Now run the actual test
echo "\n" . str_repeat("=", 60) . "\n";
echo "Running KPI Command Tests\n";
echo str_repeat("=", 60) . "\n\n";

// Import and run test class
require __DIR__ . '/tests/TestCase.php';
require __DIR__ . '/tests/Feature/CalculateKpiCommandTest.php';

$testClass = new \Tests\Feature\CalculateKpiCommandTest();
$testClass->setUpBeforeClass();

$tests = [
    'test_command_calculates_turnaround_kpi',
    'test_command_calculates_waiting_time_kpi',
    'test_command_calculates_appointment_compliance_kpi',
    'test_command_calculates_customs_completion_kpi',
    'test_command_does_not_recalculate_without_force',
    'test_command_recalculates_with_force_option',
    'test_command_handles_invalid_period',
    'test_command_handles_no_data_gracefully',
];

$passed = 0;
$failed = 0;

foreach ($tests as $testMethod) {
    echo "Running {$testMethod}...\n";
    try {
        $testClass->setUp();
        $testClass->$testMethod();
        echo "  ✓ PASSED\n\n";
        $passed++;
    } catch (\Exception $e) {
        echo "  ✗ FAILED: " . $e->getMessage() . "\n\n";
        $failed++;
    }
}

echo str_repeat("=", 60) . "\n";
echo "Results: {$passed} passed, {$failed} failed\n";
echo str_repeat("=", 60) . "\n";

exit($failed > 0 ? 1 : 0);
