@extends('layouts.app')

@section('title', 'Detalles de Junta de Operaciones')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-sgcmi-blue-900">Junta de Operaciones #{{ $operationsMeeting->id }}</h1>
                <p class="text-gray-600 mt-2">
                    {{ $operationsMeeting->meeting_date->format('d/m/Y') }} a las {{ $operationsMeeting->meeting_time }}
                </p>
            </div>
            <a href="{{ route('operations-meeting.index') }}" class="btn-secondary">
                ← Volver al Listado
            </a>
        </div>
    </div>
    
    <!-- Meeting Information -->
    <div class="card mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Información General</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Fecha de la Junta</label>
                <p class="text-gray-900">{{ $operationsMeeting->meeting_date->format('d/m/Y') }}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Hora de la Junta</label>
                <p class="text-gray-900">{{ $operationsMeeting->meeting_time }}</p>
            </div>
        </div>
    </div>
    
    <!-- Audit Information -->
    <div class="card mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Información de Auditoría</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                <label class="block text-sm font-medium text-blue-800 mb-1">📝 Creado Por</label>
                <p class="text-blue-900 font-semibold">{{ $operationsMeeting->creator->email ?? 'N/A' }}</p>
                <p class="text-sm text-blue-700 mt-1">{{ $operationsMeeting->created_at->format('d/m/Y H:i') }}</p>
            </div>
            
            @if($operationsMeeting->updated_by)
            <div class="bg-amber-50 p-4 rounded-lg border border-amber-200">
                <label class="block text-sm font-medium text-amber-800 mb-1">✏️ Última Modificación Por</label>
                <p class="text-amber-900 font-semibold">{{ $operationsMeeting->updater->email ?? 'N/A' }}</p>
                <p class="text-sm text-amber-700 mt-1">{{ $operationsMeeting->updated_at->format('d/m/Y H:i') }}</p>
            </div>
            @endif
        </div>
    </div>
    
    <!-- Attendees -->
    <div class="card mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Asistentes ({{ count($operationsMeeting->attendees) }})</h2>
        
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="table-header">
                    <tr>
                        <th class="px-4 py-3 text-left">#</th>
                        <th class="px-4 py-3 text-left">Nombre</th>
                        <th class="px-4 py-3 text-left">Cargo/Rol</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($operationsMeeting->attendees as $index => $attendee)
                        <tr class="table-row">
                            <td class="px-4 py-3">{{ $index + 1 }}</td>
                            <td class="px-4 py-3">{{ $attendee['name'] }}</td>
                            <td class="px-4 py-3">{{ $attendee['role'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Agreements -->
    <div class="card mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Acuerdos de la Junta</h2>
        <div class="bg-gray-50 p-4 rounded-lg">
            <p class="text-gray-900 whitespace-pre-line">{{ $operationsMeeting->agreements }}</p>
        </div>
    </div>
    
    <!-- 24h Schedule -->
    <div class="card">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Programación Próximas 24 Horas ({{ count($operationsMeeting->next_24h_schedule) }} operaciones)</h2>
        
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="table-header">
                    <tr>
                        <th class="px-4 py-3 text-left">#</th>
                        <th class="px-4 py-3 text-left">Nave</th>
                        <th class="px-4 py-3 text-left">Tipo de Operación</th>
                        <th class="px-4 py-3 text-left">Hora de Inicio</th>
                        <th class="px-4 py-3 text-left">Duración Estimada</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($operationsMeeting->next_24h_schedule as $index => $schedule)
                        <tr class="table-row">
                            <td class="px-4 py-3">{{ $index + 1 }}</td>
                            <td class="px-4 py-3 font-medium">{{ $schedule['vessel'] }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $badgeClass = match($schedule['operation']) {
                                        'CARGA' => 'badge-success',
                                        'DESCARGA' => 'badge-info',
                                        'REESTIBA' => 'badge-warning',
                                        default => 'badge-secondary'
                                    };
                                @endphp
                                <span class="{{ $badgeClass }}">{{ $schedule['operation'] }}</span>
                            </td>
                            <td class="px-4 py-3">{{ $schedule['start_time'] }}</td>
                            <td class="px-4 py-3">{{ $schedule['estimated_duration_h'] }} horas</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="mt-6 flex justify-between">
        <a href="{{ route('operations-meeting.index') }}" class="btn-secondary">
            ← Volver al Listado
        </a>
        
        <div class="flex space-x-3">
            @can('update', $operationsMeeting)
                <a href="{{ route('operations-meeting.edit', $operationsMeeting) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    ✏️ Editar
                </a>
            @endcan
            
            <button onclick="window.print()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                🖨️ Imprimir
            </button>
            
            @can('delete', $operationsMeeting)
                <form action="{{ route('operations-meeting.destroy', $operationsMeeting) }}" method="POST" class="inline" onsubmit="return confirm('¿Está seguro de que desea eliminar esta junta de operaciones?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        🗑️ Eliminar
                    </button>
                </form>
            @endcan
        </div>
    </div>
</div>

<style>
@media print {
    .btn-secondary,
    button[onclick="window.print()"],
    nav,
    header {
        display: none !important;
    }
}
</style>
@endsection
