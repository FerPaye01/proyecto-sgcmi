@extends('layouts.app')

@section('title', 'Citas - Módulo Terrestre')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Gestión de Citas</h1>
        <a href="{{ route('appointments.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
            + Nueva Cita
        </a>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" action="{{ route('appointments.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Desde</label>
                <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Hasta</label>
                <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                <select name="estado" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">Todos</option>
                    <option value="PROGRAMADA" {{ request('estado') == 'PROGRAMADA' ? 'selected' : '' }}>Programada</option>
                    <option value="COMPLETADA" {{ request('estado') == 'COMPLETADA' ? 'selected' : '' }}>Completada</option>
                    <option value="CANCELADA" {{ request('estado') == 'CANCELADA' ? 'selected' : '' }}>Cancelada</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md font-medium">
                    Filtrar
                </button>
            </div>
        </form>
    </div>

    <!-- Tabla de Citas -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-blue-900 text-white">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Fecha/Hora</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Camión</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Empresa</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Nave</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($appointments as $appointment)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $appointment->id }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ \Carbon\Carbon::parse($appointment->hora_programada)->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $appointment->truck->placa ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $appointment->truck->company->nombre ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $appointment->vesselCall->vessel_name ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($appointment->estado === 'PROGRAMADA')
                            <span class="px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800">Programada</span>
                        @elseif($appointment->estado === 'COMPLETADA')
                            <span class="px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-800">Completada</span>
                        @elseif($appointment->estado === 'CANCELADA')
                            <span class="px-2 py-1 text-xs font-semibold rounded bg-red-100 text-red-800">Cancelada</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-800">{{ $appointment->estado }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex space-x-2">
                            @if($appointment->estado === 'PROGRAMADA')
                                <form method="POST" action="{{ route('appointments.update', $appointment) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="estado" value="COMPLETADA">
                                    <button type="submit" class="text-green-600 hover:text-green-900">Completar</button>
                                </form>
                                <form method="POST" action="{{ route('appointments.update', $appointment) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="estado" value="CANCELADA">
                                    <button type="submit" class="text-red-600 hover:text-red-900">Cancelar</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('appointments.destroy', $appointment) }}" class="inline" onsubmit="return confirm('¿Eliminar esta cita?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-gray-600 hover:text-gray-900">Eliminar</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                        No hay citas registradas
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div class="mt-6">
        {{ $appointments->links() }}
    </div>
</div>
@endsection
