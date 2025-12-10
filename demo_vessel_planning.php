<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DEMO: Vessel Planning Implementation ===\n\n";

// Crear una llamada de nave
$vesselCall = \App\Models\VesselCall::factory()->create([
    'estado_llamada' => 'PROGRAMADA',
]);

echo "✓ Llamada de Nave creada:\n";
echo "  ID: {$vesselCall->id}\n";
echo "  Nave: {$vesselCall->vessel->name}\n";
echo "  ETA: {$vesselCall->eta}\n";
echo "  Muelle: {$vesselCall->berth->name}\n\n";

// Crear Ship Particulars
$shipParticulars = \App\Models\ShipParticulars::factory()->create([
    'vessel_call_id' => $vesselCall->id,
]);

echo "✓ Ship Particulars creado:\n";
echo "  LOA (Eslora): {$shipParticulars->loa} metros\n";
echo "  Beam (Manga): {$shipParticulars->beam} metros\n";
echo "  Draft (Calado): {$shipParticulars->draft} metros\n";
echo "  GRT: {$shipParticulars->grt} toneladas\n";
echo "  DWT: {$shipParticulars->dwt} toneladas\n";
if ($shipParticulars->dangerous_cargo) {
    echo "  ⚠️  Carga Peligrosa: Sí (Clases IMDG: " . implode(', ', $shipParticulars->dangerous_cargo['imdg_classes']) . ")\n";
}
echo "\n";

// Crear Loading Plans
echo "✓ Loading Plans creados:\n";
$operations = ['DESCARGA', 'CARGA', 'REESTIBA'];
foreach ($operations as $index => $operation) {
    $loadingPlan = \App\Models\LoadingPlan::factory()->create([
        'vessel_call_id' => $vesselCall->id,
        'operation_type' => $operation,
        'sequence_order' => $index + 1,
        'status' => 'PLANIFICADO',
    ]);
    echo "  {$loadingPlan->sequence_order}. {$loadingPlan->operation_type} - ";
    echo "Duración estimada: {$loadingPlan->estimated_duration_h}h - ";
    echo "Cuadrilla: {$loadingPlan->crew_required} personas\n";
}
echo "\n";

// Crear Resource Allocations
echo "✓ Resource Allocations creados:\n";
$resources = [
    ['type' => 'EQUIPO', 'name' => 'Grúa Pórtico 1'],
    ['type' => 'EQUIPO', 'name' => 'Reach Stacker A'],
    ['type' => 'CUADRILLA', 'name' => 'Cuadrilla A'],
    ['type' => 'GAVIERO', 'name' => 'Gaviero Principal'],
];

foreach ($resources as $resource) {
    $allocation = \App\Models\ResourceAllocation::factory()->create([
        'vessel_call_id' => $vesselCall->id,
        'resource_type' => $resource['type'],
        'resource_name' => $resource['name'],
    ]);
    echo "  - {$allocation->resource_type}: {$allocation->resource_name} ";
    echo "(Turno: {$allocation->shift}, Cantidad: {$allocation->quantity})\n";
}
echo "\n";

// Crear Operations Meeting
$meeting = \App\Models\OperationsMeeting::factory()->create();
echo "✓ Operations Meeting creado:\n";
echo "  Fecha: {$meeting->meeting_date->format('d/m/Y')}\n";
echo "  Hora: {$meeting->meeting_time}\n";
echo "  Asistentes: " . count($meeting->attendees) . " personas\n";
echo "  Operaciones programadas próximas 24h: " . count($meeting->next_24h_schedule) . "\n\n";

// Verificar relaciones
echo "=== Verificación de Relaciones ===\n\n";

$vesselCallWithRelations = \App\Models\VesselCall::with([
    'shipParticulars',
    'loadingPlans',
    'resourceAllocations'
])->find($vesselCall->id);

echo "✓ Llamada de Nave #{$vesselCallWithRelations->id} tiene:\n";
echo "  - Ship Particulars: " . ($vesselCallWithRelations->shipParticulars ? 'Sí' : 'No') . "\n";
echo "  - Loading Plans: {$vesselCallWithRelations->loadingPlans->count()}\n";
echo "  - Resource Allocations: {$vesselCallWithRelations->resourceAllocations->count()}\n\n";

echo "=== Consulta SQL de Ejemplo ===\n\n";
echo "Para ver todos los datos en PostgreSQL:\n";
echo "SELECT * FROM portuario.ship_particulars WHERE vessel_call_id = {$vesselCall->id};\n";
echo "SELECT * FROM portuario.loading_plan WHERE vessel_call_id = {$vesselCall->id};\n";
echo "SELECT * FROM portuario.resource_allocation WHERE vessel_call_id = {$vesselCall->id};\n\n";

echo "✅ Demo completado exitosamente!\n";
