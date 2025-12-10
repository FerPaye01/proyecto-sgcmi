<?php

/**
 * Verification script for Vessel Planning Controllers
 * This script verifies that the vessel planning functionality is working correctly
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Vessel Planning Verification ===\n\n";

// Check if controllers exist
echo "1. Checking Controllers...\n";
$controllers = [
    'App\Http\Controllers\Portuario\VesselPlanningController',
    'App\Http\Controllers\Portuario\ResourcePlanningController',
];

foreach ($controllers as $controller) {
    if (class_exists($controller)) {
        echo "   ✓ $controller exists\n";
    } else {
        echo "   ✗ $controller NOT FOUND\n";
    }
}

// Check if views exist
echo "\n2. Checking Views...\n";
$views = [
    'portuario.vessel-planning.service-request',
    'portuario.vessel-planning.validate-arrival',
    'portuario.vessel-planning.resource-allocation',
    'portuario.vessel-planning.show',
];

foreach ($views as $view) {
    if (view()->exists($view)) {
        echo "   ✓ $view exists\n";
    } else {
        echo "   ✗ $view NOT FOUND\n";
    }
}

// Check if routes exist
echo "\n3. Checking Routes...\n";
$routes = [
    'vessel-planning.service-request',
    'vessel-planning.store-service-request',
    'vessel-planning.show',
    'vessel-planning.validate-arrival',
    'vessel-planning.validate-arrival.post',
    'resource-planning.index',
    'resource-planning.allocate',
    'resource-planning.update',
];

foreach ($routes as $routeName) {
    try {
        $route = route($routeName, ['vesselCall' => 1, 'allocation' => 1], false);
        echo "   ✓ $routeName -> $route\n";
    } catch (\Exception $e) {
        echo "   ✗ $routeName NOT FOUND\n";
    }
}

// Check if models have relationships
echo "\n4. Checking Model Relationships...\n";
try {
    $vesselCall = new \App\Models\VesselCall();
    
    $relationships = [
        'shipParticulars',
        'loadingPlans',
        'resourceAllocations',
    ];
    
    foreach ($relationships as $relationship) {
        if (method_exists($vesselCall, $relationship)) {
            echo "   ✓ VesselCall::$relationship() exists\n";
        } else {
            echo "   ✗ VesselCall::$relationship() NOT FOUND\n";
        }
    }
} catch (\Exception $e) {
    echo "   ✗ Error checking relationships: " . $e->getMessage() . "\n";
}

// Check database tables
echo "\n5. Checking Database Tables...\n";
$tables = [
    'portuario.ship_particulars',
    'portuario.loading_plan',
    'portuario.resource_allocation',
    'portuario.operations_meeting',
];

foreach ($tables as $table) {
    try {
        $exists = \Illuminate\Support\Facades\DB::select("SELECT EXISTS (
            SELECT FROM information_schema.tables 
            WHERE table_schema = 'portuario' 
            AND table_name = '" . str_replace('portuario.', '', $table) . "'
        )")[0]->exists;
        
        if ($exists) {
            echo "   ✓ $table exists\n";
        } else {
            echo "   ✗ $table NOT FOUND\n";
        }
    } catch (\Exception $e) {
        echo "   ✗ Error checking $table: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Verification Complete ===\n";

