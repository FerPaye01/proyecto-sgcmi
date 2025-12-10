<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\ReportService;
use App\Models\User;

echo "=== PRUEBA DE TODOS LOS REPORTES ===\n\n";

$reportService = app(ReportService::class);

// Obtener un usuario para scoping
$user = User::first();

$reportes = [
    'R1' => fn() => $reportService->generateR1([]),
    'R3' => fn() => $reportService->generateR3([]),
    'R4' => fn() => $reportService->generateR4([], $user),
    'R5' => fn() => $reportService->generateR5([], $user),
    'R6' => fn() => $reportService->generateR6([]),
    'R7' => fn() => $reportService->generateR7([]),
    'R8' => fn() => $reportService->generateR8([]),
    'R9' => fn() => $reportService->generateR9([]),
    'R10' => fn() => $reportService->generateR10([]),
    'R11' => fn() => $reportService->generateR11([]),
    'R12' => fn() => $reportService->generateR12([]),
];

$exitosos = 0;
$errores = 0;

foreach ($reportes as $nombre => $generador) {
    try {
        $resultado = $generador();
        
        // Verificar estructura básica
        if (!is_array($resultado)) {
            throw new Exception("Resultado no es un array");
        }
        
        // Verificar que tiene datos
        $tieneData = isset($resultado['data']) || isset($resultado['kpis']) || isset($resultado['alertas']);
        
        if (!$tieneData) {
            throw new Exception("Resultado no tiene estructura esperada");
        }
        
        echo "✓ {$nombre}: OK\n";
        $exitosos++;
        
    } catch (\Exception $e) {
        echo "✗ {$nombre}: {$e->getMessage()}\n";
        $errores++;
    }
}

echo "\n=== RESUMEN ===\n";
echo "Exitosos: {$exitosos}/11\n";
echo "Errores: {$errores}/11\n";

if ($errores === 0) {
    echo "\n✓ TODOS LOS REPORTES FUNCIONAN CORRECTAMENTE\n";
    exit(0);
} else {
    echo "\n✗ ALGUNOS REPORTES TIENEN ERRORES\n";
    exit(1);
}
