@extends('layouts.app')

@section('title', 'Llamadas de Naves')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-sgcmi-blue-900">Llamadas de Naves</h1>
        
        @can('SCHEDULE_WRITE')
            <div class="flex space-x-3">
                <a href="{{ route('operations-meeting.index') }}" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    📅 Juntas de Operaciones
                </a>
                <a href="{{ route('vessel-planning.service-request') }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    📋 Nueva Solicitud de Servicio
                </a>
                <a href="{{ route('resource-planning.index') }}" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    👥 Gestión de Recursos
                </a>
                <a href="{{ route('vessel-calls.create') }}" class="btn-primary">
                    Nueva Llamada
                </a>
            </div>
        @endcan
    </div>
    
    <!-- Filters -->
    <x-filter-panel :showBerth="true" :showVessel="true" :showEstado="true">
        @if(isset($berths))
            @foreach($berths as $berth)
                <option value="{{ $berth->id }}">{{ $berth->name }}</option>
            @endforeach
        @endif
    </x-filter-panel>
    
    <!-- Vessel Calls Table -->
    <div class="card">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="table-header">
                    <tr>
                        <th class="px-4 py-3 text-left">ID</th>
                        <th class="px-4 py-3 text-left">Nave</th>
                        <th class="px-4 py-3 text-left">Viaje</th>
                        <th class="px-4 py-3 text-left">Muelle</th>
                        <th class="px-4 py-3 text-left">ETA</th>
                        <th class="px-4 py-3 text-left">ETB</th>
                        <th class="px-4 py-3 text-left">Estado</th>
                        <th class="px-4 py-3 text-left">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vesselCalls as $vesselCall)
                        <tr class="table-row">
                            <td class="px-4 py-3">{{ $vesselCall->id }}</td>
                            <td class="px-4 py-3">{{ $vesselCall->vessel->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3">{{ $vesselCall->viaje_id }}</td>
                            <td class="px-4 py-3">{{ $vesselCall->berth->name ?? 'Sin asignar' }}</td>
                            <td class="px-4 py-3">{{ $vesselCall->eta?->format('d/m/Y H:i') ?? 'N/A' }}</td>
                            <td class="px-4 py-3">{{ $vesselCall->etb?->format('d/m/Y H:i') ?? 'N/A' }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $badgeClass = match($vesselCall->estado_llamada) {
                                        'PROGRAMADA' => 'badge-info',
                                        'EN_TRANSITO' => 'badge-warning',
                                        'ATRACADA', 'OPERANDO' => 'badge-success',
                                        'ZARPO' => 'badge-secondary',
                                        default => 'badge-info'
                                    };
                                @endphp
                                <span class="{{ $badgeClass }}">{{ $vesselCall->estado_llamada }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex space-x-2">
                                    <a href="{{ route('vessel-calls.show', $vesselCall) }}" class="text-blue-600 hover:underline">
                                        Ver
                                    </a>
                                    @can('SCHEDULE_WRITE')
                                        <a href="{{ route('vessel-calls.edit', $vesselCall) }}" class="text-green-600 hover:underline">
                                            Editar
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                No hay llamadas de naves registradas
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if(isset($vesselCalls) && method_exists($vesselCalls, 'links'))
            <div class="mt-4">
                {{ $vesselCalls->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
