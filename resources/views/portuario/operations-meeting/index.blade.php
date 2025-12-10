@extends('layouts.app')

@section('title', 'Juntas de Operaciones')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-sgcmi-blue-900">Juntas de Operaciones</h1>
        
        @can('create', App\Models\OperationsMeeting::class)
            <a href="{{ route('operations-meeting.create') }}" class="btn-primary">
                Nueva Junta de Operaciones
            </a>
        @endcan
    </div>
    
    <!-- Filters -->
    <div class="card mb-6">
        <form method="GET" action="{{ route('operations-meeting.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Fecha Desde</label>
                <input type="date" 
                       id="date_from" 
                       name="date_from" 
                       value="{{ request('date_from') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sgcmi-blue-500 focus:border-transparent">
            </div>
            
            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Fecha Hasta</label>
                <input type="date" 
                       id="date_to" 
                       name="date_to" 
                       value="{{ request('date_to') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sgcmi-blue-500 focus:border-transparent">
            </div>
            
            <div class="flex items-end space-x-2">
                <button type="submit" class="btn-primary flex-1">
                    Filtrar
                </button>
                <a href="{{ route('operations-meeting.index') }}" class="btn-secondary flex-1">
                    Limpiar
                </a>
            </div>
        </form>
    </div>
    
    <!-- Meetings Table -->
    <div class="card">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="table-header">
                    <tr>
                        <th class="px-4 py-3 text-left">ID</th>
                        <th class="px-4 py-3 text-left">Fecha</th>
                        <th class="px-4 py-3 text-left">Hora</th>
                        <th class="px-4 py-3 text-left">Asistentes</th>
                        <th class="px-4 py-3 text-left">Operaciones Programadas</th>
                        <th class="px-4 py-3 text-left">Creado Por</th>
                        <th class="px-4 py-3 text-left">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($meetings as $meeting)
                        <tr class="table-row">
                            <td class="px-4 py-3">{{ $meeting->id }}</td>
                            <td class="px-4 py-3">{{ $meeting->meeting_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">{{ $meeting->meeting_time }}</td>
                            <td class="px-4 py-3">{{ count($meeting->attendees) }}</td>
                            <td class="px-4 py-3">{{ count($meeting->next_24h_schedule) }}</td>
                            <td class="px-4 py-3 text-sm">{{ $meeting->creator->email ?? 'N/A' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex space-x-2">
                                    <a href="{{ route('operations-meeting.show', $meeting) }}" class="text-blue-600 hover:underline">
                                        Ver
                                    </a>
                                    @can('update', $meeting)
                                        <a href="{{ route('operations-meeting.edit', $meeting) }}" class="text-green-600 hover:underline">
                                            Editar
                                        </a>
                                    @endcan
                                    @can('delete', $meeting)
                                        <form action="{{ route('operations-meeting.destroy', $meeting) }}" method="POST" class="inline" onsubmit="return confirm('¿Está seguro?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">
                                                Eliminar
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                No hay juntas de operaciones registradas
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if(method_exists($meetings, 'links'))
            <div class="mt-4">
                {{ $meetings->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
