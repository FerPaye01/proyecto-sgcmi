<?php

declare(strict_types=1);

/**
 * Script de verificación para ReportController@r9
 * Verifica que el método r9 existe y tiene la estructura correcta
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Verificación de ReportController@r9 ===\n\n";

// Verificar que el controlador existe
$controllerClass = \App\Http\Controllers\ReportController::class;
if (!class_exists($controllerClass)) {
    echo "❌ ERROR: La clase ReportController no existe\n";
    exit(1);
}

echo "✓ Clase ReportController existe\n";

// Verificar que el método r9 existe
if (!method_exists($controllerClass, 'r9')) {
    echo "❌ ERROR: El método r9 no existe en ReportController\n";
    exit(1);
}

echo "✓ Método r9 existe\n";

// Verificar que el método tiene la firma correcta
$reflection = new \ReflectionMethod($controllerClass, 'r9');
$parameters = $reflection->getParameters();

if (count($parameters) !== 1) {
    echo "❌ ERROR: El método r9 debe tener exactamente 1 parámetro\n";
    exit(1);
}

echo "✓ Método r9 tiene 1 parámetro\n";

$param = $parameters[0];
if ($param->getName() !== 'request') {
    echo "❌ ERROR: El parámetro debe llamarse 'request'\n";
    exit(1);
}

echo "✓ Parámetro se llama 'request'\n";

// Verificar el tipo de retorno
$returnType = $reflection->getReturnType();
if ($returnType === null || $returnType->getName() !== 'Illuminate\View\View') {
    echo "❌ ERROR: El método debe retornar Illuminate\View\View\n";
    exit(1);
}

echo "✓ Método retorna Illuminate\View\View\n";

// Verificar que la ruta existe
$router = app('router');
$routes = $router->getRoutes();
$r9Route = $routes->getByName('reports.r9');

if ($r9Route === null) {
    echo "❌ ERROR: La ruta 'reports.r9' no existe\n";
    exit(1);
}

echo "✓ Ruta 'reports.r9' existe\n";

// Verificar la URI de la ruta
$uri = $r9Route->uri();
if ($uri !== 'reports/cus/doc-incidents') {
    echo "❌ ERROR: La URI de la ruta debe ser 'reports/cus/doc-incidents', pero es '{$uri}'\n";
    exit(1);
}

echo "✓ URI de la ruta es correcta: {$uri}\n";

// Verificar que tiene el middleware de permisos
$middleware = $r9Route->middleware();
if (!in_array('permission:CUS_REPORT_READ', $middleware)) {
    echo "❌ ERROR: La ruta debe tener el middleware 'permission:CUS_REPORT_READ'\n";
    exit(1);
}

echo "✓ Ruta tiene el middleware de permisos correcto\n";

// Verificar que el método generateR9 existe en ReportService
$serviceClass = \App\Services\ReportService::class;
if (!method_exists($serviceClass, 'generateR9')) {
    echo "❌ ERROR: El método generateR9 no existe en ReportService\n";
    exit(1);
}

echo "✓ Método generateR9 existe en ReportService\n";

echo "\n=== ✅ Todas las verificaciones pasaron correctamente ===\n";
echo "\nEl método ReportController@r9 está correctamente implementado y configurado.\n";
echo "Próximo paso: Crear la vista 'reports.cus.doc-incidents'\n";

exit(0);
