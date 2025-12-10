@extends('layouts.app')

@section('title', 'Trámites Aduaneros')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Gestión de Trámites Aduaneros</h1>
        <a href="{{ route('tramites.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
            + Nuevo Trámite
        </a>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" action="{{ route('tramites.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                    <option value="INICIADO" {{ request('estado') == 'INICIADO' ? 'selected' : '' }}>Iniciado</option>
                    <option value="EN_PROCESO" {{ request('estado') == 'EN_PROCESO' ? 'selected' : '' }}>En Proceso</option>
                    <option value="COMPLETADO" {{ request('estado') == 'COMPLETADO' ? 'selected' : '' }}>Completado</option>
                    <option value="RECHAZADO" {{ request('estado') == 'RECHAZADO' ? 'selected' : '' }}>Rechazado</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md font-medium">
                    Filtrar
                </button>
            </div>
        </form>
    </div>

    <!-- Tabla de Trámites -->
    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-blue-900 text-white">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Trámite Ext</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Régimen</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Nave</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Entidad</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Fecha Inicio</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($tramites as $tramite)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $tramite->id }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ substr($tramite->tramite_ext_id, 0, 8) }}...
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $tramite->regimen }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $tramite->vesselCall->vessel_name ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $tramite->entidad->name ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ \Carbon\Carbon::parse($tramite->fecha_inicio)->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($tramite->estado === 'INICIADO')
                            <span class="px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800">Iniciado</span>
                        @elseif($tramite->estado === 'EN_PROCESO')
                            <span class="px-2 py-1 text-xs font-semibold rounded bg-yellow-100 text-yellow-800">En Proceso</span>
                        @elseif($tramite->estado === 'COMPLETADO')
                            <span class="px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-800">Completado</span>
                        @elseif($tramite->estado === 'RECHAZADO')
                            <span class="px-2 py-1 text-xs font-semibold rounded bg-red-100 text-red-800">Rechazado</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-800">{{ $tramite->estado }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex space-x-2">
                            <a href="{{ route('tramites.show', $tramite) }}" class="text-blue-600 hover:text-blue-900">Ver</a>
                            <a href="{{ route('tramites.edit', $tramite) }}" class="text-green-600 hover:text-green-900">Editar</a>
                            <form method="POST" action="{{ route('tramites.destroy', $tramite) }}" class="inline" onsubmit="return confirm('¿Eliminar este trámite?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">Eliminar</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                        No hay trámites registrados
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div class="mt-6">
        {{ $tramites->links() }}
    </div>
</div>
@endsection
