<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ARREGLANDO PERMISOS PARA JUNTAS DE OPERACIONES ===\n\n";

// 1. Crear el permiso PORT_REPORT_WRITE si no existe
$permission = App\Models\Permission::where('code', 'PORT_REPORT_WRITE')->first();
if (!$permission) {
    $permission = App\Models\Permission::create([
        'code' => 'PORT_REPORT_WRITE',
        'name' => 'PORT_REPORT_WRITE'
    ]);
    echo "✅ Permiso PORT_REPORT_WRITE creado\n";
} else {
    echo "✅ Permiso PORT_REPORT_WRITE ya existe\n";
}

// 2. Asignar el permiso a PLANIFICADOR_PUERTO
$role = App\Models\Role::where('code', 'PLANIFICADOR_PUERTO')->first();
if ($role) {
    if (!$role->permissions()->where('code', 'PORT_REPORT_WRITE')->exists()) {
        $role->permissions()->attach($permission);
        echo "✅ Permiso PORT_REPORT_WRITE asignado a PLANIFICADOR_PUERTO\n";
    } else {
        echo "✅ PLANIFICADOR_PUERTO ya tiene el permiso PORT_REPORT_WRITE\n";
    }
} else {
    echo "❌ Rol PLANIFICADOR_PUERTO no encontrado\n";
}

// 3. Asignar el permiso a OPERACIONES_PUERTO también
$role2 = App\Models\Role::where('code', 'OPERACIONES_PUERTO')->first();
if ($role2) {
    if (!$role2->permissions()->where('code', 'PORT_REPORT_WRITE')->exists()) {
        $role2->permissions()->attach($permission);
        echo "✅ Permiso PORT_REPORT_WRITE asignado a OPERACIONES_PUERTO\n";
    } else {
        echo "✅ OPERACIONES_PUERTO ya tiene el permiso PORT_REPORT_WRITE\n";
    }
}

// 4. Verificar que el usuario planificador@sgcmi.pe ahora tiene el permiso
$user = App\Models\User::where('email', 'planificador@sgcmi.pe')->first();
if ($user) {
    $hasPermission = $user->hasPermission('PORT_REPORT_WRITE');
    if ($hasPermission) {
        echo "✅ Usuario planificador@sgcmi.pe ahora tiene PORT_REPORT_WRITE\n";
    } else {
        echo "❌ Usuario planificador@sgcmi.pe NO tiene PORT_REPORT_WRITE\n";
    }
    
    // Verificar policy
    $canCreate = Gate::forUser($user)->allows('create', App\Models\OperationsMeeting::class);
    if ($canCreate) {
        echo "✅ Usuario puede crear juntas de operaciones\n";
    } else {
        echo "❌ Usuario NO puede crear juntas de operaciones\n";
    }
}

echo "\n=== ARREGLO COMPLETADO ===\n";
echo "\nPor favor, cierra sesión y vuelve a iniciar sesión para que los cambios surtan efecto.\n";
