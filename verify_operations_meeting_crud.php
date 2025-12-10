<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== VERIFICACIÓN: CRUD Completo de Juntas de Operaciones ===\n\n";

// 1. Verificar rutas
echo "Rutas disponibles:\n";
$routes = Route::getRoutes();
$operationsMeetingRoutes = [];
foreach ($routes as $route) {
    if (str_contains($route->uri(), 'operations-meeting')) {
        $method = $route->methods()[0];
        $uri = $route->uri();
        $name = $route->getName();
        $operationsMeetingRoutes[] = "$method /$uri → $name";
    }
}

foreach ($operationsMeetingRoutes as $route) {
    echo "  ✅ $route\n";
}
echo "\n";

// 2. Verificar permisos del usuario
$user = App\Models\User::where('email', 'planificador@sgcmi.pe')->first();
if ($user) {
    echo "Permisos de planificador@sgcmi.pe:\n";
    
    // Crear una junta de prueba
    $meeting = App\Models\OperationsMeeting::factory()->create([
        'created_by' => $user->id,
    ]);
    
    $canView = Gate::forUser($user)->allows('view', $meeting);
    $canUpdate = Gate::forUser($user)->allows('update', $meeting);
    $canDelete = Gate::forUser($user)->allows('delete', $meeting);
    
    echo "  " . ($canView ? '✅' : '❌') . " Ver junta\n";
    echo "  " . ($canUpdate ? '✅' : '❌') . " Editar junta\n";
    echo "  " . ($canDelete ? '✅' : '❌') . " Eliminar junta\n";
    
    // Limpiar
    $meeting->delete();
    echo "\n";
}

// 3. Verificar vistas
echo "Vistas creadas:\n";
$views = [
    'resources/views/portuario/operations-meeting/index.blade.php',
    'resources/views/portuario/operations-meeting/create.blade.php',
    'resources/views/portuario/operations-meeting/show.blade.php',
    'resources/views/portuario/operations-meeting/edit.blade.php',
];

foreach ($views as $view) {
    $exists = file_exists(__DIR__ . '/' . $view);
    echo "  " . ($exists ? '✅' : '❌') . " $view\n";
}
echo "\n";

// 4. Verificar métodos del controlador
echo "Métodos del controlador:\n";
$controller = new ReflectionClass(App\Http\Controllers\Portuario\OperationsMeetingController::class);
$methods = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];

foreach ($methods as $method) {
    $exists = $controller->hasMethod($method);
    echo "  " . ($exists ? '✅' : '❌') . " $method()\n";
}
echo "\n";

echo "=== VERIFICACIÓN COMPLETADA ===\n";
echo "\nPuedes probar las funcionalidades en:\n";
echo "  - Listado: http://localhost:8000/portuario/operations-meeting\n";
echo "  - Crear: http://localhost:8000/portuario/operations-meeting/create\n";
echo "  - Ver: http://localhost:8000/portuario/operations-meeting/{id}\n";
echo "  - Editar: http://localhost:8000/portuario/operations-meeting/{id}/edit\n";
echo "  - Eliminar: Botón en la vista de detalles o listado\n";
