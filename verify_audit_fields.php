<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\OperationsMeeting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "=== Verificación de Campos de Auditoría ===\n\n";

// Get a test user
$user = User::first();
if (!$user) {
    echo "❌ No hay usuarios en el sistema\n";
    exit(1);
}

echo "✓ Usuario de prueba: {$user->email}\n\n";

// Simulate authentication
Auth::login($user);

// Create a test meeting
echo "1. Creando junta de operaciones...\n";
$meeting = OperationsMeeting::create([
    'meeting_date' => now()->addDay(),
    'meeting_time' => '10:00',
    'attendees' => [
        ['name' => 'Juan Pérez', 'role' => 'Planificador'],
        ['name' => 'María García', 'role' => 'Operador'],
    ],
    'agreements' => 'Acuerdos de prueba para verificación de auditoría',
    'next_24h_schedule' => [
        [
            'vessel' => 'MSC OSCAR',
            'operation' => 'CARGA',
            'start_time' => '14:00',
            'estimated_duration_h' => 6,
        ],
    ],
    'created_by' => $user->id,
]);

echo "✓ Junta creada con ID: {$meeting->id}\n";
echo "✓ Creado por: {$meeting->creator->email}\n";
echo "✓ Fecha de creación: {$meeting->created_at}\n";
echo "✓ updated_by inicial: " . ($meeting->updated_by ? 'SET' : 'NULL') . "\n\n";

// Get another user for update test
$updater = User::where('id', '!=', $user->id)->first();
if (!$updater) {
    echo "⚠️  No hay segundo usuario para probar actualización\n";
    $updater = $user;
}

Auth::login($updater);

// Update the meeting
echo "2. Actualizando junta de operaciones...\n";
$meeting->update([
    'agreements' => 'Acuerdos actualizados por ' . $updater->email,
    'updated_by' => $updater->id,
]);

$meeting->refresh();

echo "✓ Junta actualizada\n";
echo "✓ Creado por: {$meeting->creator->email}\n";
echo "✓ Modificado por: {$meeting->updater->email}\n";
echo "✓ Fecha de modificación: {$meeting->updated_at}\n\n";

// Verify relationships
echo "3. Verificando relaciones...\n";
$meetingWithRelations = OperationsMeeting::with(['creator', 'updater'])->find($meeting->id);

if ($meetingWithRelations->creator) {
    echo "✓ Relación creator cargada correctamente\n";
    echo "  - Email: {$meetingWithRelations->creator->email}\n";
} else {
    echo "❌ Relación creator no cargada\n";
}

if ($meetingWithRelations->updater) {
    echo "✓ Relación updater cargada correctamente\n";
    echo "  - Email: {$meetingWithRelations->updater->email}\n";
} else {
    echo "⚠️  Relación updater no cargada (puede ser NULL si no se ha actualizado)\n";
}

echo "\n4. Limpiando datos de prueba...\n";
$meeting->delete();
echo "✓ Junta de prueba eliminada\n";

echo "\n=== ✅ Verificación Completada ===\n";
