<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DIAGNÓSTICO: Juntas de Operaciones ===\n\n";

// 1. Verificar usuario
$email = 'planificador@sgcmi.pe';
$user = App\Models\User::where('email', $email)->first();

if (!$user) {
    echo "❌ Usuario '$email' no encontrado\n";
    echo "\nUsuarios disponibles:\n";
    $users = App\Models\User::all();
    foreach ($users as $u) {
        echo "  - {$u->email}\n";
    }
    exit(1);
}

echo "✅ Usuario encontrado: {$user->name} ({$user->email})\n\n";

// 2. Verificar roles
echo "Roles del usuario:\n";
$roles = $user->roles;
foreach ($roles as $role) {
    echo "  - {$role->name} ({$role->code})\n";
}
echo "\n";

// 3. Verificar permisos
echo "Permisos del usuario:\n";
$permissions = $user->roles->flatMap(function($role) {
    return $role->permissions;
})->pluck('code')->unique();

foreach ($permissions as $perm) {
    echo "  - $perm\n";
}
echo "\n";

// 4. Verificar permisos específicos necesarios
echo "Verificación de permisos necesarios:\n";
$requiredPerms = ['PORT_REPORT_READ', 'PORT_REPORT_WRITE', 'ADMIN'];
foreach ($requiredPerms as $perm) {
    $has = $user->hasPermission($perm);
    $status = $has ? '✅' : '❌';
    echo "  $status $perm\n";
}
echo "\n";

// 5. Verificar rutas
echo "Rutas de operations-meeting:\n";
$routes = Route::getRoutes();
foreach ($routes as $route) {
    if (str_contains($route->uri(), 'operations-meeting')) {
        echo "  - {$route->methods()[0]} /{$route->uri()}\n";
    }
}
echo "\n";

// 6. Verificar policy
echo "Verificación de Policy:\n";
try {
    $canViewAny = Gate::forUser($user)->allows('viewAny', App\Models\OperationsMeeting::class);
    $canCreate = Gate::forUser($user)->allows('create', App\Models\OperationsMeeting::class);
    
    echo "  " . ($canViewAny ? '✅' : '❌') . " viewAny\n";
    echo "  " . ($canCreate ? '✅' : '❌') . " create\n";
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// 7. Contar juntas existentes
$count = App\Models\OperationsMeeting::count();
echo "Juntas de operaciones en BD: $count\n";

echo "\n=== FIN DEL DIAGNÓSTICO ===\n";
