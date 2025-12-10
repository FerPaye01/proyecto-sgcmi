<?php

declare(strict_types=1);

// Set up environment
putenv('APP_ENV=testing');
putenv('APP_MAINTENANCE_DRIVER=file');
putenv('BCRYPT_ROUNDS=4');
putenv('CACHE_STORE=array');
putenv('DB_CONNECTION=pgsql');
putenv('DB_DATABASE=sgcmi_test');
putenv('MAIL_MAILER=array');
putenv('PULSE_ENABLED=false');
putenv('QUEUE_CONNECTION=sync');
putenv('SESSION_DRIVER=array');
putenv('TELESCOPE_ENABLED=false');

// Load Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';

// Create the application
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Import test class
require __DIR__ . '/tests/Feature/CalculateKpiCommandTest.php';

// Run tests manually
$test = new \Tests\Feature\CalculateKpiCommandTest();
$test->setUp();

echo "Running test_command_calculates_turnaround_kpi...\n";
try {
    $test->test_command_calculates_turnaround_kpi();
    echo "✓ PASSED\n";
} catch (\Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
}

echo "\nRunning test_command_calculates_waiting_time_kpi...\n";
try {
    $test->setUp();
    $test->test_command_calculates_waiting_time_kpi();
    echo "✓ PASSED\n";
} catch (\Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
}

echo "\nRunning test_command_calculates_appointment_compliance_kpi...\n";
try {
    $test->setUp();
    $test->test_command_calculates_appointment_compliance_kpi();
    echo "✓ PASSED\n";
} catch (\Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
}

echo "\nRunning test_command_calculates_customs_completion_kpi...\n";
try {
    $test->setUp();
    $test->test_command_calculates_customs_completion_kpi();
    echo "✓ PASSED\n";
} catch (\Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
}

echo "\nRunning test_command_does_not_recalculate_without_force...\n";
try {
    $test->setUp();
    $test->test_command_does_not_recalculate_without_force();
    echo "✓ PASSED\n";
} catch (\Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
}

echo "\nRunning test_command_recalculates_with_force_option...\n";
try {
    $test->setUp();
    $test->test_command_recalculates_with_force_option();
    echo "✓ PASSED\n";
} catch (\Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
}

echo "\nRunning test_command_handles_invalid_period...\n";
try {
    $test->setUp();
    $test->test_command_handles_invalid_period();
    echo "✓ PASSED\n";
} catch (\Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
}

echo "\nRunning test_command_handles_no_data_gracefully...\n";
try {
    $test->setUp();
    $test->test_command_handles_no_data_gracefully();
    echo "✓ PASSED\n";
} catch (\Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
}
