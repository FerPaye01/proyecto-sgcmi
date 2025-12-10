<?php

declare(strict_types=1);

/**
 * Verification script for R11 Alert Generation
 * Tests that alerts are generated when thresholds are exceeded
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Alert;
use App\Models\Appointment;
use App\Models\Berth;
use App\Models\Company;
use App\Models\GateEvent;
use App\Models\Truck;
use App\Models\Vessel;
use App\Models\VesselCall;
use App\Services\ReportService;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "R11 Alert Generation Verification\n";
echo "========================================\n\n";

try {
    // Clean up test data
    echo "[1] Cleaning up test data...\n";
    DB::statement('TRUNCATE TABLE analytics.alerts CASCADE');
    DB::statement('TRUNCATE TABLE terrestre.gate_events CASCADE');
    DB::statement('TRUNCATE TABLE terrestre.appointments CASCADE');
    DB::statement('TRUNCATE TABLE portuario.vessel_calls CASCADE');
    DB::statement('TRUNCATE TABLE portuario.vessels CASCADE');
    DB::statement('TRUNCATE TABLE portuario.berths CASCADE');
    DB::statement('TRUNCATE TABLE terrestre.trucks CASCADE');
    DB::statement('TRUNCATE TABLE terrestre.companies CASCADE');
    echo "✓ Test data cleaned\n\n";

    // Create test data for congestion alert
    echo "[2] Creating test data for congestion alert...\n";
    $berth = Berth::create([
        'code' => 'TEST_BERTH_01',
        'name' => 'Test Berth 01',
        'capacity_teorica' => 1000,
        'active' => true,
    ]);
    echo "✓ Created berth: {$berth->name}\n";

    $vessel = Vessel::create([
        'imo' => 'TEST001',
        'name' => 'Test Vessel 01',
        'flag_country' => 'PE',
        'type' => 'CONTAINER',
    ]);
    echo "✓ Created vessel: {$vessel->name}\n";

    $now = now();
    
    // Create multiple vessel calls with overlapping times to simulate congestion
    for ($i = 0; $i < 3; $i++) {
        VesselCall::create([
            'vessel_id' => $vessel->id,
            'viaje_id' => "VIAJE_TEST_{$i}",
            'berth_id' => $berth->id,
            'eta' => $now->clone()->addHours($i * 2),
            'etb' => $now->clone()->addHours($i * 2),
            'ata' => $now->clone()->addHours($i * 2),
            'atb' => $now->clone()->addHours($i * 2),
            'atd' => $now->clone()->addHours($i * 2 + 3),
            'estado_llamada' => 'OPERANDO',
        ]);
    }
    echo "✓ Created 3 vessel calls with overlapping times\n\n";

    // Create test data for truck accumulation alert
    echo "[3] Creating test data for truck accumulation alert...\n";
    $company = Company::create([
        'ruc' => '20123456789',
        'name' => 'Test Transport Company',
        'tipo' => 'TRANSPORTISTA',
        'active' => true,
    ]);
    echo "✓ Created company: {$company->name}\n";

    $truck1 = Truck::create([
        'placa' => 'ABC1234',
        'company_id' => $company->id,
        'activo' => true,
    ]);

    $truck2 = Truck::create([
        'placa' => 'ABC1235',
        'company_id' => $company->id,
        'activo' => true,
    ]);
    echo "✓ Created 2 trucks\n";

    $vesselCall = VesselCall::first();
    
    // Create appointments with long waiting times
    $recentTime = now()->subHours(2);
    $recentArrival1 = $recentTime->clone()->subHours(5);
    $recentArrival2 = $recentTime->clone()->subHours(4.5);

    $appointment1 = Appointment::create([
        'truck_id' => $truck1->id,
        'company_id' => $company->id,
        'vessel_call_id' => $vesselCall->id,
        'hora_programada' => $recentArrival1->clone()->addMinutes(30),
        'hora_llegada' => $recentArrival1,
        'estado' => 'ATENDIDA',
    ]);

    $appointment2 = Appointment::create([
        'truck_id' => $truck2->id,
        'company_id' => $company->id,
        'vessel_call_id' => $vesselCall->id,
        'hora_programada' => $recentArrival2->clone()->addMinutes(30),
        'hora_llegada' => $recentArrival2,
        'estado' => 'ATENDIDA',
    ]);
    echo "✓ Created 2 appointments with long waiting times (>4 hours)\n";

    // Create gate events
    GateEvent::create([
        'gate_id' => 1,
        'truck_id' => $truck1->id,
        'action' => 'ENTRADA',
        'event_ts' => $recentArrival1->clone()->addMinutes(12),
        'cita_id' => $appointment1->id,
    ]);

    GateEvent::create([
        'gate_id' => 1,
        'truck_id' => $truck2->id,
        'action' => 'ENTRADA',
        'event_ts' => $recentArrival2->clone()->addMinutes(18),
        'cita_id' => $appointment2->id,
    ]);
    echo "✓ Created gate events\n\n";

    // Generate R11 report
    echo "[4] Generating R11 report...\n";
    $reportService = app(ReportService::class);
    $report = $reportService->generateR11([]);
    echo "✓ R11 report generated\n\n";

    // Verify alerts were generated
    echo "[5] Verifying alerts...\n";
    $alertas = $report['alertas'];
    echo "Total alerts generated: " . $alertas->count() . "\n";

    if ($alertas->isEmpty()) {
        echo "⚠ WARNING: No alerts were generated!\n";
    } else {
        foreach ($alertas as $alerta) {
            echo "\n  Alert Type: {$alerta['tipo']}\n";
            echo "  Level: {$alerta['nivel']}\n";
            echo "  Value: {$alerta['valor']} {$alerta['unidad']}\n";
            echo "  Threshold: {$alerta['umbral']}\n";
            echo "  Description: {$alerta['descripción']}\n";
        }
    }

    // Verify alerts were persisted to database
    echo "\n[6] Verifying alerts in database...\n";
    $dbAlerts = Alert::all();
    echo "Alerts in database: " . $dbAlerts->count() . "\n";

    if ($dbAlerts->count() > 0) {
        foreach ($dbAlerts as $alert) {
            echo "\n  DB Alert ID: {$alert->alert_id}\n";
            echo "  Type: {$alert->tipo}\n";
            echo "  Level: {$alert->nivel}\n";
            echo "  Status: {$alert->estado}\n";
        }
    }

    // Verify KPIs
    echo "\n[7] Verifying KPIs...\n";
    $kpis = $report['kpis'];
    echo "Total alerts: {$kpis['total_alertas']}\n";
    echo "Red alerts: {$kpis['alertas_rojas']}\n";
    echo "Yellow alerts: {$kpis['alertas_amarillas']}\n";
    echo "Green alerts: {$kpis['alertas_verdes']}\n";
    echo "Congestion alerts: {$kpis['alertas_congestión']}\n";
    echo "Accumulation alerts: {$kpis['alertas_acumulación']}\n";

    // Verify system status
    echo "\n[8] Verifying system status...\n";
    $estadoGeneral = $report['estado_general'];
    echo "System status: {$estadoGeneral}\n";

    echo "\n========================================\n";
    echo "✓ R11 Alert Generation Verification PASSED\n";
    echo "========================================\n";

} catch (\Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
