<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\VesselCall;
use App\Models\Appointment;
use App\Models\GateEvent;
use App\Models\Tramite;
use App\Models\TramiteEvent;
use App\Models\Berth;
use App\Models\Vessel;
use App\Models\Gate;
use App\Models\Company;
use App\Models\Truck;
use App\Models\Entidad;
use Carbon\Carbon;

echo "=== GENERANDO DATOS DE PRUEBA PARA REPORTES ===\n\n";

// Limpiar datos existentes
echo "1. Limpiando datos existentes...\n";
VesselCall::truncate();
Appointment::truncate();
GateEvent::truncate();
Tramite::truncate();
TramiteEvent::truncate();

// Obtener referencias
$berths = Berth::where('active', true)->get();
$vessels = Vessel::all();
$gates = Gate::where('activo', true)->get();
$companies = Company::where('active', true)->get();
$trucks = Truck::all();
$entidades = Entidad::all();

if ($berths->isEmpty() || $vessels->isEmpty() || $gates->isEmpty() || $companies->isEmpty() || $entidades->isEmpty()) {
    echo "✗ ERROR: Faltan datos maestros. Ejecuta: php artisan db:seed\n";
    exit(1);
}

echo "   ✓ Datos limpios\n";

// 2. Generar Vessel Calls (R1, R3)
echo "\n2. Generando Vessel Calls (20 registros)...\n";

$now = Carbon::now();
for ($i = 0; $i < 20; $i++) {
    $eta = $now->copy()->addDays(rand(1, 10))->setHour(rand(6, 18))->setMinute(0);
    $ata = $eta->copy()->addHours(rand(-2, 4)); // Puede llegar antes o después
    $etb = $ata->copy()->addHours(rand(12, 24));
    $atb = $etb->copy()->addHours(rand(-2, 2));
    $atd = $atb->copy()->addHours(rand(12, 24));

    VesselCall::create([
        'viaje_id' => "VIAJE-" . str_pad((string)($i + 1), 4, '0', STR_PAD_LEFT),
        'vessel_id' => $vessels->random()->id,
        'berth_id' => $berths->random()->id,
        'eta' => $eta,
        'ata' => $ata,
        'etb' => $etb,
        'atb' => $atb,
        'atd' => $atd,
    ]);
}

echo "   ✓ 20 Vessel Calls creados\n";

// 3. Generar Appointments (R4, R5, R6)
echo "\n3. Generando Appointments (50 registros)...\n";

for ($i = 0; $i < 50; $i++) {
    $horaProgramada = $now->copy()->addDays(rand(0, 7))->setHour(rand(6, 18))->setMinute(rand(0, 59));
    
    // 80% de las citas tienen llegada
    $tieneEntrada = rand(1, 100) <= 80;
    $horaLlegada = $tieneEntrada ? $horaProgramada->copy()->addMinutes(rand(-30, 120)) : null;
    
    $estado = $tieneEntrada ? (rand(1, 100) <= 90 ? 'ATENDIDA' : 'CONFIRMADA') : 'NO_SHOW';

    $appointment = Appointment::create([
        'truck_id' => $trucks->random()->id,
        'company_id' => $companies->random()->id,
        'gate_id' => $gates->random()->id,
        'vessel_call_id' => VesselCall::inRandomOrder()->first()->id,
        'hora_programada' => $horaProgramada,
        'hora_llegada' => $horaLlegada,
        'estado' => $estado,
    ]);

    // Generar eventos de gate si hay llegada
    if ($tieneEntrada && $horaLlegada) {
        $gateId = $gates->random()->id;
        
        GateEvent::create([
            'gate_id' => $gateId,
            'truck_id' => $appointment->truck_id,
            'action' => 'ENTRADA',
            'event_ts' => $horaLlegada,
        ]);

        // Salida 30 minutos a 2 horas después
        $horaSalida = $horaLlegada->copy()->addMinutes(rand(30, 120));
        GateEvent::create([
            'gate_id' => $gateId,
            'truck_id' => $appointment->truck_id,
            'action' => 'SALIDA',
            'event_ts' => $horaSalida,
        ]);
    }
}

echo "   ✓ 50 Appointments creados con eventos de gate\n";

// 4. Generar Trámites (R7, R8, R9)
echo "\n4. Generando Trámites (100 registros)...\n";

$regimenes = ['IMPORTACION', 'EXPORTACION', 'TRANSITO'];
$estados = ['INICIADO', 'EN_REVISION', 'OBSERVADO', 'APROBADO', 'RECHAZADO'];

for ($i = 0; $i < 100; $i++) {
    $fechaInicio = $now->copy()->subDays(rand(1, 30))->setHour(rand(6, 18))->setMinute(0);
    
    // 70% de los trámites están aprobados
    $estado = rand(1, 100) <= 70 ? 'APROBADO' : $estados[array_rand($estados)];
    
    $fechaFin = null;
    if ($estado === 'APROBADO') {
        $fechaFin = $fechaInicio->copy()->addHours(rand(4, 48));
    } elseif ($estado !== 'INICIADO') {
        $fechaFin = $fechaInicio->copy()->addHours(rand(2, 24));
    }

    $tramite = Tramite::create([
        'tramite_ext_id' => "TRM-" . str_pad((string)($i + 1), 6, '0', STR_PAD_LEFT),
        'vessel_call_id' => VesselCall::inRandomOrder()->first()->id,
        'entidad_id' => $entidades->random()->id,
        'regimen' => $regimenes[array_rand($regimenes)],
        'estado' => $estado,
        'fecha_inicio' => $fechaInicio,
        'fecha_fin' => $fechaFin,
    ]);

    // Generar eventos de trámite
    $estadosSecuencia = ['INICIADO', 'EN_REVISION'];
    
    if ($estado === 'OBSERVADO' || $estado === 'APROBADO' || $estado === 'RECHAZADO') {
        $estadosSecuencia[] = 'OBSERVADO';
    }
    
    if ($estado === 'APROBADO') {
        $estadosSecuencia[] = 'APROBADO';
    } elseif ($estado === 'RECHAZADO') {
        $estadosSecuencia[] = 'RECHAZADO';
    }

    $tiempoActual = $fechaInicio->copy();
    foreach ($estadosSecuencia as $est) {
        $tiempoActual = $tiempoActual->copy()->addHours(rand(1, 4));
        
        TramiteEvent::create([
            'tramite_id' => $tramite->id,
            'estado' => $est,
            'event_ts' => $tiempoActual,
        ]);
    }
}

echo "   ✓ 100 Trámites creados con eventos\n";

// 5. Verificar datos generados
echo "\n5. Verificación de datos:\n";

echo "   • VesselCalls: " . VesselCall::count() . "\n";
echo "   • Appointments: " . Appointment::count() . "\n";
echo "   • GateEvents: " . GateEvent::count() . "\n";
echo "   • Tramites: " . Tramite::count() . "\n";
echo "   • TramiteEvents: " . TramiteEvent::count() . "\n";

echo "\n=== DATOS GENERADOS EXITOSAMENTE ===\n";
echo "\nAhora puedes acceder a los reportes:\n";
echo "  • R1: /reports/port/schedule-vs-actual\n";
echo "  • R3: /reports/port/berth-utilization\n";
echo "  • R4: /reports/road/waiting-time\n";
echo "  • R5: /reports/road/appointments-compliance\n";
echo "  • R6: /reports/road/gate-productivity\n";
echo "  • R7: /reports/cus/status-by-vessel\n";
echo "  • R8: /reports/cus/dispatch-time\n";
echo "  • R9: /reports/cus/doc-incidents\n";
echo "  • R10: /reports/kpi/panel\n";
echo "  • R11: /reports/analytics/early-warning\n";
echo "  • R12: /reports/sla/compliance\n";
