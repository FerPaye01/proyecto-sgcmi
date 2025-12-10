<?php

declare(strict_types=1);

/**
 * Integration test for KPI command
 * This script tests that the kpi:calculate command properly updates KPI values
 */

// Set up environment
putenv('APP_ENV=testing');
putenv('DB_CONNECTION=pgsql');
putenv('DB_DATABASE=sgcmi_test');

// Load Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';

// Bootstrap
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\KpiDefinition;
use App\Models\KpiValue;
use App\Models\VesselCall;
use App\Models\Vessel;
use App\Models\Berth;
use App\Models\Appointment;
use App\Models\Truck;
use App\Models\Company;
use App\Models\GateEvent;
use App\Models\Gate;
use App\Models\Tramite;
use App\Models\Entidad;
use Illuminate\Support\Facades\DB;

echo "KPI Command Integration Test\n";
echo str_repeat("=", 60) . "\n\n";

// Test 1: Verify command calculates turnaround KPI
echo "Test 1: Command calculates turnaround KPI\n";
echo "-------------------------------------------\n";

try {
    // Clean up
    DB::table('analytics.kpi_value')->delete();
    DB::table('portuario.vessel_call')->delete();
    DB::table('portuario.vessel')->delete();
    DB::table('portuario.berth')->delete();
    
    // Create test data
    $berth = Berth::factory()->create();
    $vessel = Vessel::factory()->create();
    
    $ata = now()->subDay()->startOfDay()->addHours(8);
    $atd = now()->startOfDay()->addHours(8); // 24 hours turnaround
    
    VesselCall::factory()->create([
        'vessel_id' => $vessel->id,
        'berth_id' => $berth->id,
        'ata' => $ata,
        'atd' => $atd,
    ]);
    
    // Run command
    $exitCode = $kernel->call('kpi:calculate', ['--period' => 'today']);
    
    if ($exitCode !== 0) {
        echo "✗ FAILED: Command returned exit code {$exitCode}\n";
    } else {
        // Verify KPI was created
        $kpiDef = KpiDefinition::where('code', 'turnaround_h')->first();
        $kpiValue = KpiValue::where('kpi_id', $kpiDef->id)
            ->where('periodo', now()->toDateString())
            ->first();
        
        if ($kpiValue && $kpiValue->valor == 24.0) {
            echo "✓ PASSED: Turnaround KPI calculated correctly (24.0 hours)\n";
        } else {
            echo "✗ FAILED: KPI value incorrect. Expected 24.0, got " . ($kpiValue?->valor ?? 'null') . "\n";
        }
    }
} catch (\Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Verify command calculates waiting time KPI
echo "Test 2: Command calculates waiting time KPI\n";
echo "-------------------------------------------\n";

try {
    // Clean up
    DB::table('analytics.kpi_value')->delete();
    DB::table('terrestre.gate_event')->delete();
    DB::table('terrestre.appointment')->delete();
    DB::table('terrestre.truck')->delete();
    DB::table('terrestre.company')->delete();
    DB::table('terrestre.gate')->delete();
    
    // Create test data
    $company = Company::factory()->create();
    $truck = Truck::factory()->create(['company_id' => $company->id]);
    $gate = Gate::factory()->create();
    
    $horaLlegada = now()->startOfDay()->addHours(10);
    $horaEntrada = now()->startOfDay()->addHours(12); // 2 hours waiting
    
    $appointment = Appointment::factory()->create([
        'truck_id' => $truck->id,
        'company_id' => $company->id,
        'hora_llegada' => $horaLlegada,
        'estado' => 'ATENDIDA',
    ]);
    
    GateEvent::factory()->create([
        'gate_id' => $gate->id,
        'truck_id' => $truck->id,
        'cita_id' => $appointment->id,
        'action' => 'ENTRADA',
        'event_ts' => $horaEntrada,
    ]);
    
    // Run command
    $exitCode = $kernel->call('kpi:calculate', ['--period' => 'today']);
    
    if ($exitCode !== 0) {
        echo "✗ FAILED: Command returned exit code {$exitCode}\n";
    } else {
        // Verify KPI was created
        $kpiDef = KpiDefinition::where('code', 'espera_camion_h')->first();
        $kpiValue = KpiValue::where('kpi_id', $kpiDef->id)
            ->where('periodo', now()->toDateString())
            ->first();
        
        if ($kpiValue && $kpiValue->valor == 2.0) {
            echo "✓ PASSED: Waiting time KPI calculated correctly (2.0 hours)\n";
        } else {
            echo "✗ FAILED: KPI value incorrect. Expected 2.0, got " . ($kpiValue?->valor ?? 'null') . "\n";
        }
    }
} catch (\Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Verify command calculates appointment compliance KPI
echo "Test 3: Command calculates appointment compliance KPI\n";
echo "-------------------------------------------\n";

try {
    // Clean up
    DB::table('analytics.kpi_value')->delete();
    DB::table('terrestre.appointment')->delete();
    DB::table('terrestre.truck')->delete();
    DB::table('terrestre.company')->delete();
    
    // Create test data
    $company = Company::factory()->create();
    $truck = Truck::factory()->create(['company_id' => $company->id]);
    
    $horaProgramada = now()->startOfDay()->addHours(10);
    
    // On time (±15 min)
    Appointment::factory()->create([
        'truck_id' => $truck->id,
        'company_id' => $company->id,
        'hora_programada' => $horaProgramada,
        'hora_llegada' => $horaProgramada->copy()->addMinutes(10),
        'estado' => 'ATENDIDA',
    ]);
    
    // Late (>15 min)
    Appointment::factory()->create([
        'truck_id' => $truck->id,
        'company_id' => $company->id,
        'hora_programada' => $horaProgramada,
        'hora_llegada' => $horaProgramada->copy()->addMinutes(30),
        'estado' => 'ATENDIDA',
    ]);
    
    // Run command
    $exitCode = $kernel->call('kpi:calculate', ['--period' => 'today']);
    
    if ($exitCode !== 0) {
        echo "✗ FAILED: Command returned exit code {$exitCode}\n";
    } else {
        // Verify KPI was created
        $kpiDef = KpiDefinition::where('code', 'cumpl_citas_pct')->first();
        $kpiValue = KpiValue::where('kpi_id', $kpiDef->id)
            ->where('periodo', now()->toDateString())
            ->first();
        
        if ($kpiValue && $kpiValue->valor == 50.0) {
            echo "✓ PASSED: Appointment compliance KPI calculated correctly (50.0%)\n";
        } else {
            echo "✗ FAILED: KPI value incorrect. Expected 50.0, got " . ($kpiValue?->valor ?? 'null') . "\n";
        }
    }
} catch (\Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Verify command calculates customs completion KPI
echo "Test 4: Command calculates customs completion KPI\n";
echo "-------------------------------------------\n";

try {
    // Clean up
    DB::table('analytics.kpi_value')->delete();
    DB::table('aduanas.tramite')->delete();
    DB::table('aduanas.entidad')->delete();
    DB::table('portuario.vessel_call')->delete();
    DB::table('portuario.vessel')->delete();
    DB::table('portuario.berth')->delete();
    
    // Create test data
    $entidad = Entidad::factory()->create();
    $berth = Berth::factory()->create();
    $vessel = Vessel::factory()->create();
    $vesselCall = VesselCall::factory()->create([
        'vessel_id' => $vessel->id,
        'berth_id' => $berth->id,
    ]);
    
    // Approved
    Tramite::factory()->create([
        'vessel_call_id' => $vesselCall->id,
        'entidad_id' => $entidad->id,
        'estado' => 'APROBADO',
        'fecha_inicio' => now()->subDays(2),
        'fecha_fin' => now(),
    ]);
    
    // Rejected
    Tramite::factory()->create([
        'vessel_call_id' => $vesselCall->id,
        'entidad_id' => $entidad->id,
        'estado' => 'RECHAZADO',
        'fecha_inicio' => now()->subDays(2),
        'fecha_fin' => now(),
    ]);
    
    // Run command
    $exitCode = $kernel->call('kpi:calculate', ['--period' => 'today']);
    
    if ($exitCode !== 0) {
        echo "✗ FAILED: Command returned exit code {$exitCode}\n";
    } else {
        // Verify KPI was created
        $kpiDef = KpiDefinition::where('code', 'tramites_ok_pct')->first();
        $kpiValue = KpiValue::where('kpi_id', $kpiDef->id)
            ->where('periodo', now()->toDateString())
            ->first();
        
        if ($kpiValue && $kpiValue->valor == 50.0) {
            echo "✓ PASSED: Customs completion KPI calculated correctly (50.0%)\n";
        } else {
            echo "✗ FAILED: KPI value incorrect. Expected 50.0, got " . ($kpiValue?->valor ?? 'null') . "\n";
        }
    }
} catch (\Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 5: Verify command does not recalculate without --force
echo "Test 5: Command does not recalculate without --force\n";
echo "-------------------------------------------\n";

try {
    // Clean up and create existing KPI value
    DB::table('analytics.kpi_value')->delete();
    
    $kpiDef = KpiDefinition::where('code', 'turnaround_h')->first();
    KpiValue::create([
        'kpi_id' => $kpiDef->id,
        'periodo' => now()->toDateString(),
        'valor' => 99.99,
        'meta' => 48.0,
        'fuente' => 'test',
    ]);
    
    // Run command without --force
    $exitCode = $kernel->call('kpi:calculate', ['--period' => 'today']);
    
    if ($exitCode !== 0) {
        echo "✗ FAILED: Command returned exit code {$exitCode}\n";
    } else {
        // Verify value was not changed
        $kpiValue = KpiValue::where('kpi_id', $kpiDef->id)
            ->where('periodo', now()->toDateString())
            ->first();
        
        if ($kpiValue && $kpiValue->valor == 99.99) {
            echo "✓ PASSED: KPI value was not recalculated (still 99.99)\n";
        } else {
            echo "✗ FAILED: KPI value was changed. Expected 99.99, got " . ($kpiValue?->valor ?? 'null') . "\n";
        }
    }
} catch (\Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 6: Verify command recalculates with --force
echo "Test 6: Command recalculates with --force\n";
echo "-------------------------------------------\n";

try {
    // Clean up
    DB::table('analytics.kpi_value')->delete();
    DB::table('portuario.vessel_call')->delete();
    DB::table('portuario.vessel')->delete();
    DB::table('portuario.berth')->delete();
    
    // Create existing KPI value
    $kpiDef = KpiDefinition::where('code', 'turnaround_h')->first();
    KpiValue::create([
        'kpi_id' => $kpiDef->id,
        'periodo' => now()->toDateString(),
        'valor' => 99.99,
        'meta' => 48.0,
        'fuente' => 'test',
    ]);
    
    // Create test data
    $berth = Berth::factory()->create();
    $vessel = Vessel::factory()->create();
    VesselCall::factory()->create([
        'vessel_id' => $vessel->id,
        'berth_id' => $berth->id,
        'ata' => now()->subDay()->startOfDay()->addHours(8),
        'atd' => now()->startOfDay()->addHours(8),
    ]);
    
    // Run command with --force
    $exitCode = $kernel->call('kpi:calculate', ['--period' => 'today', '--force' => true]);
    
    if ($exitCode !== 0) {
        echo "✗ FAILED: Command returned exit code {$exitCode}\n";
    } else {
        // Verify value was recalculated
        $kpiValue = KpiValue::where('kpi_id', $kpiDef->id)
            ->where('periodo', now()->toDateString())
            ->first();
        
        if ($kpiValue && $kpiValue->valor == 24.0) {
            echo "✓ PASSED: KPI value was recalculated (now 24.0)\n";
        } else {
            echo "✗ FAILED: KPI value incorrect. Expected 24.0, got " . ($kpiValue?->valor ?? 'null') . "\n";
        }
    }
} catch (\Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 7: Verify command handles invalid period
echo "Test 7: Command handles invalid period\n";
echo "-------------------------------------------\n";

try {
    $exitCode = $kernel->call('kpi:calculate', ['--period' => 'invalid']);
    
    if ($exitCode !== 0) {
        echo "✓ PASSED: Command returned error for invalid period\n";
    } else {
        echo "✗ FAILED: Command should have failed for invalid period\n";
    }
} catch (\Exception $e) {
    echo "✓ PASSED: Command threw exception for invalid period\n";
}

echo "\n";

// Test 8: Verify command handles no data gracefully
echo "Test 8: Command handles no data gracefully\n";
echo "-------------------------------------------\n";

try {
    // Clean up all data
    DB::table('analytics.kpi_value')->delete();
    DB::table('portuario.vessel_call')->delete();
    DB::table('terrestre.appointment')->delete();
    DB::table('aduanas.tramite')->delete();
    
    // Run command
    $exitCode = $kernel->call('kpi:calculate', ['--period' => 'today']);
    
    if ($exitCode === 0) {
        // Verify no KPI values were created
        $count = KpiValue::where('periodo', now()->toDateString())->count();
        if ($count === 0) {
            echo "✓ PASSED: Command handled no data gracefully\n";
        } else {
            echo "✗ FAILED: KPI values were created when there was no data\n";
        }
    } else {
        echo "✗ FAILED: Command returned error code {$exitCode}\n";
    }
} catch (\Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "Integration tests completed\n";
echo str_repeat("=", 60) . "\n";
