<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Sprint 1 Verification ===\n\n";

// Test Models
echo "1. Testing Models...\n";
try {
    $model = new \App\Models\ShipParticulars();
    echo "   ✓ ShipParticulars model exists\n";
} catch (\Exception $e) {
    echo "   ✗ ShipParticulars: " . $e->getMessage() . "\n";
}

try {
    $model = new \App\Models\LoadingPlan();
    echo "   ✓ LoadingPlan model exists\n";
} catch (\Exception $e) {
    echo "   ✗ LoadingPlan: " . $e->getMessage() . "\n";
}

try {
    $model = new \App\Models\ResourceAllocation();
    echo "   ✓ ResourceAllocation model exists\n";
} catch (\Exception $e) {
    echo "   ✗ ResourceAllocation: " . $e->getMessage() . "\n";
}

try {
    $model = new \App\Models\OperationsMeeting();
    echo "   ✓ OperationsMeeting model exists\n";
} catch (\Exception $e) {
    echo "   ✗ OperationsMeeting: " . $e->getMessage() . "\n";
}

// Test Database Tables
echo "\n2. Testing Database Tables...\n";
try {
    $count = \App\Models\ShipParticulars::count();
    echo "   ✓ ship_particulars table exists (records: $count)\n";
} catch (\Exception $e) {
    echo "   ✗ ship_particulars table: " . $e->getMessage() . "\n";
}

try {
    $count = \App\Models\LoadingPlan::count();
    echo "   ✓ loading_plan table exists (records: $count)\n";
} catch (\Exception $e) {
    echo "   ✗ loading_plan table: " . $e->getMessage() . "\n";
}

try {
    $count = \App\Models\ResourceAllocation::count();
    echo "   ✓ resource_allocation table exists (records: $count)\n";
} catch (\Exception $e) {
    echo "   ✗ resource_allocation table: " . $e->getMessage() . "\n";
}

try {
    $count = \App\Models\OperationsMeeting::count();
    echo "   ✓ operations_meeting table exists (records: $count)\n";
} catch (\Exception $e) {
    echo "   ✗ operations_meeting table: " . $e->getMessage() . "\n";
}

// Test Controllers
echo "\n3. Testing Controllers...\n";
if (class_exists('\App\Http\Controllers\Portuario\VesselPlanningController')) {
    echo "   ✓ VesselPlanningController exists\n";
} else {
    echo "   ✗ VesselPlanningController missing\n";
}

if (class_exists('\App\Http\Controllers\Portuario\ResourcePlanningController')) {
    echo "   ✓ ResourcePlanningController exists\n";
} else {
    echo "   ✗ ResourcePlanningController missing\n";
}

if (class_exists('\App\Http\Controllers\Portuario\OperationsMeetingController')) {
    echo "   ✓ OperationsMeetingController exists\n";
} else {
    echo "   ✗ OperationsMeetingController missing\n";
}

// Test Routes
echo "\n4. Testing Routes...\n";
$routes = \Illuminate\Support\Facades\Route::getRoutes();
$requiredRoutes = [
    'vessel-planning.service-request',
    'vessel-planning.show',
    'vessel-planning.validate-arrival',
    'resource-planning.index',
    'resource-planning.allocate',
    'operations-meeting.index',
    'operations-meeting.create',
    'operations-meeting.store',
    'operations-meeting.show',
];

foreach ($requiredRoutes as $routeName) {
    if ($routes->hasNamedRoute($routeName)) {
        echo "   ✓ Route '$routeName' registered\n";
    } else {
        echo "   ✗ Route '$routeName' missing\n";
    }
}

echo "\n=== Sprint 1 Verification Complete ===\n";
