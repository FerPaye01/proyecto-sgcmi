@extends('layouts.app')

@section('title', 'Asignación de Recursos')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Asignación de Recursos</h1>
            <a href="{{ route('vessel-calls.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                Volver
            </a>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <form method="GET" action="{{ route('resource-planning.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="vessel_call_id" class="block text-sm font-medium text-gray-700">Llamada de Nave</label>
                    <select name="vessel_call_id" id="vessel_call_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todas</option>
                        @foreach($vesselCalls as $vc)
                            <option value="{{ $vc->id }}" {{ request('vessel_call_id') == $vc->id ? 'selected' : '' }}>
                                {{ $vc->vessel->name }} - {{ $vc->eta->format('d/m/Y') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="resource_type" class="block text-sm font-medium text-gray-700">Tipo de Recurso</label>
                    <select name="resource_type" id="resource_type"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="EQUIPO" {{ request('resource_type') == 'EQUIPO' ? 'selected' : '' }}>Equipo</option>
                        <option value="CUADRILLA" {{ request('resource_type') == 'CUADRILLA' ? 'selected' : '' }}>Cuadrilla</option>
                        <option value="GAVIERO" {{ request('resource_type') == 'GAVIERO' ? 'selected' : '' }}>Gaviero</option>
                    </select>
                </div>

                <div>
                    <label for="shift" class="block text-sm font-medium text-gray-700">Turno</label>
                    <select name="shift" id="shift"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="MAÑANA" {{ request('shift') == 'MAÑANA' ? 'selected' : '' }}>Mañana</option>
                        <option value="TARDE" {{ request('shift') == 'TARDE' ? 'selected' : '' }}>Tarde</option>
                        <option value="NOCHE" {{ request('shift') == 'NOCHE' ? 'selected' : '' }}>Noche</option>
                    </select>
                </div>

                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                        Filtrar
                    </button>
                </div>
            </form>
        </div>

        <!-- Allocations Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Nave
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tipo de Recurso
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Recurso
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cantidad
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Turno
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Fecha Asignación
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Asignado Por
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($allocations as $allocation)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $allocation->vesselCall->vessel->name }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ $allocation->vesselCall->viaje_id ?? 'N/A' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded
                                    @if($allocation->resource_type === 'EQUIPO') bg-blue-100 text-blue-800
                                    @elseif($allocation->resource_type === 'CUADRILLA') bg-green-100 text-green-800
                                    @else bg-purple-100 text-purple-800
                                    @endif">
                                    {{ $allocation->resource_type }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">{{ $allocation->resource_name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $allocation->quantity }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded
                                    @if($allocation->shift === 'MAÑANA') bg-yellow-100 text-yellow-800
                                    @elseif($allocation->shift === 'TARDE') bg-orange-100 text-orange-800
                                    @else bg-indigo-100 text-indigo-800
                                    @endif">
                                    {{ $allocation->shift }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $allocation->allocated_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $allocation->creator->full_name ?? $allocation->creator->username }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                No se encontraron asignaciones de recursos
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($allocations->hasPages())
            <div class="mt-6">
                {{ $allocations->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
