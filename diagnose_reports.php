<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;

echo "=== DIAGNÓSTICO DE REPORTES ===\n\n";

// 1. Verificar conexión a BD
echo "1. Conexión a BD: ";
try {
    DB::connection()->getPdo();
    echo "✓ OK\n";
} catch (\Exception $e) {
    echo "✗ ERROR: {$e->getMessage()}\n";
    exit(1);
}

// 2. Verificar usuarios
echo "\n2. Usuarios en BD: ";
$userCount = User::count();
echo "{$userCount} usuarios\n";

if ($userCount === 0) {
    echo "   ⚠ No hay usuarios. Ejecuta: php artisan db:seed\n";
}

// 3. Verificar datos de prueba
echo "\n3. Datos de prueba:\n";

$tables = [
    'portuario.vessel_calls' => \App\Models\VesselCall::class,
    'terrestre.appointments' => \App\Models\Appointment::class,
    'aduanas.tramites' => \App\Models\Tramite::class,
    'terrestre.gate_events' => \App\Models\GateEvent::class,
];

foreach ($tables as $table => $model) {
    try {
        $count = $model::count();
        $status = $count > 0 ? "✓" : "⚠";
        echo "   {$status} {$table}: {$count} registros\n";
    } catch (\Exception $e) {
        echo "   ✗ {$table}: {$e->getMessage()}\n";
    }
}

// 4. Verificar permisos
echo "\n4. Permisos de reportes:\n";

$reportPermissions = [
    'PORT_REPORT_READ',
    'ROAD_REPORT_READ',
    'CUS_REPORT_READ',
    'KPI_READ',
    'SLA_READ',
];

foreach ($reportPermissions as $perm) {
    $exists = DB::table('admin.permissions')->where('code', $perm)->exists();
    $status = $exists ? "✓" : "✗";
    echo "   {$status} {$perm}\n";
}

// 5. Verificar rutas
echo "\n5. Rutas de reportes:\n";

$routes = [
    'reports.r1' => '/reports/port/schedule-vs-actual',
    'reports.r3' => '/reports/port/berth-utilization',
    'reports.r4' => '/reports/road/waiting-time',
    'reports.r5' => '/reports/road/appointments-compliance',
    'reports.r6' => '/reports/road/gate-productivity',
    'reports.r7' => '/reports/cus/status-by-vessel',
    'reports.r8' => '/reports/cus/dispatch-time',
    'reports.r9' => '/reports/cus/doc-incidents',
    'reports.r10' => '/reports/kpi/panel',
    'reports.r11' => '/reports/analytics/early-warning',
    'reports.r12' => '/reports/sla/compliance',
];

foreach ($routes as $name => $path) {
    echo "   • {$name} → {$path}\n";
}

// 6. Verificar vistas
echo "\n6. Vistas de reportes:\n";

$views = [
    'reports.port.schedule-vs-actual',
    'reports.port.berth-utilization',
    'reports.road.waiting-time',
    'reports.road.appointments-compliance',
    'reports.road.gate-productivity',
    'reports.cus.status-by-vessel',
    'reports.cus.dispatch-time',
    'reports.cus.doc-incidents',
    'reports.kpi.panel',
    'reports.analytics.early-warning',
    'reports.sla.compliance',
];

$viewPath = resource_path('views');
foreach ($views as $view) {
    $file = $viewPath . '/' . str_replace('.', '/', $view) . '.blade.php';
    $exists = file_exists($file) ? "✓" : "✗";
    echo "   {$exists} {$view}\n";
}

echo "\n=== FIN DIAGNÓSTICO ===\n";
