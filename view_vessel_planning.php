<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== VESSEL PLANNING TABLES - ESTRUCTURA ===\n\n";

$tables = [
    'portuario.ship_particulars',
    'portuario.loading_plan',
    'portuario.resource_allocation',
    'portuario.operations_meeting'
];

foreach ($tables as $table) {
    echo "📋 Tabla: {$table}\n";
    echo str_repeat('-', 60) . "\n";
    
    $columns = DB::select("
        SELECT column_name, data_type, character_maximum_length, is_nullable
        FROM information_schema.columns
        WHERE table_schema = 'portuario' 
        AND table_name = ?
        ORDER BY ordinal_position
    ", [explode('.', $table)[1]]);
    
    foreach ($columns as $column) {
        $type = $column->data_type;
        if ($column->character_maximum_length) {
            $type .= "({$column->character_maximum_length})";
        }
        $nullable = $column->is_nullable === 'YES' ? 'NULL' : 'NOT NULL';
        echo sprintf("  %-25s %-20s %s\n", $column->column_name, $type, $nullable);
    }
    echo "\n";
}

echo "=== MODELOS ELOQUENT CREADOS ===\n\n";

$models = [
    'ShipParticulars' => \App\Models\ShipParticulars::class,
    'LoadingPlan' => \App\Models\LoadingPlan::class,
    'ResourceAllocation' => \App\Models\ResourceAllocation::class,
    'OperationsMeeting' => \App\Models\OperationsMeeting::class,
];

foreach ($models as $name => $class) {
    echo "✓ {$name}\n";
    echo "  Clase: {$class}\n";
    echo "  Tabla: " . (new $class)->getTable() . "\n";
    echo "  Fillable: " . implode(', ', (new $class)->getFillable()) . "\n";
    echo "\n";
}

echo "=== CONTEO DE REGISTROS ===\n\n";

foreach ($models as $name => $class) {
    $count = $class::count();
    echo "  {$name}: {$count} registros\n";
}

echo "\n✅ Implementación verificada exitosamente!\n";
