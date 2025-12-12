@extends('layouts.app')

@section('title', 'Detalle del Manifiesto de Carga')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Manifest Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Manifiesto de Carga: {{ $manifest->manifest_number }}</h2>
            <div class="flex space-x-2">
                <a href="{{ route('cargo.item.create', ['manifest_id' => $manifest->id]) }}" 
                   class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded transition-colors">
                    + Agregar Ítem
                </a>
                <a href="{{ route('vessel-calls.show', $manifest->vessel_call_id) }}" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded transition-colors">
                    Volver
                </a>
            </div>
        </div>

        <!-- Manifest Details -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Llamada de Nave</label>
                <p class="text-lg font-semibold text-gray-900">
                    {{ $manifest->vesselCall->vessel->name }}
                </p>
                <p class="text-sm text-gray-600">
                    Viaje: {{ $manifest->vesselCall->viaje_id ?? 'N/A' }}
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Fecha del Manifiesto</label>
                <p class="text-lg font-semibold text-gray-900">
                    {{ \Carbon\Carbon::parse($manifest->manifest_date)->format('d/m/Y') }}
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Total de Ítems</label>
                <p class="text-lg font-semibold text-gray-900">
                    {{ $manifest->total_items }}
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Peso Total</label>
                <p class="text-lg font-semibold text-gray-900">
                    {{ number_format($manifest->total_weight_kg, 2) }} kg
                </p>
            </div>

            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-600 mb-1">Documento</label>
                @if($manifest->document_url)
                    <a href="{{ $manifest->document_url }}" 
                       target="_blank"
                       class="text-blue-600 hover:text-blue-800 underline">
                        Ver documento digitalizado
                    </a>
                @else
                    <p class="text-gray-500 italic">No hay documento adjunto</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Cargo Items List -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Ítems de Carga</h3>

        @if($manifest->cargoItems->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Ítem #
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Descripción
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tipo
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Contenedor/Precinto
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Peso (kg)
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                B/L
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Ubicación
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Estado
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($manifest->cargoItems as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $item->item_number }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ Str::limit($item->description, 50) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        {{ $item->cargo_type === 'CONTENEDOR' ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $item->cargo_type === 'GRANEL' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $item->cargo_type === 'CARGA_GENERAL' ? 'bg-purple-100 text-purple-800' : '' }}">
                                        {{ $item->cargo_type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($item->container_number)
                                        <div>{{ $item->container_number }}</div>
                                        @if($item->seal_number)
                                            <div class="text-xs text-gray-500">Precinto: {{ $item->seal_number }}</div>
                                        @endif
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ number_format($item->weight_kg, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $item->bl_number ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($item->yardLocation)
                                        <div class="text-xs">
                                            <div>{{ $item->yardLocation->zone_code }}</div>
                                            @if($item->yardLocation->block_code)
                                                <div class="text-gray-500">
                                                    Bloque: {{ $item->yardLocation->block_code }}
                                                    @if($item->yardLocation->row_code)
                                                        / Fila: {{ $item->yardLocation->row_code }}
                                                    @endif
                                                    @if($item->yardLocation->tier)
                                                        / Nivel: {{ $item->yardLocation->tier }}
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-400">Sin asignar</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        {{ $item->status === 'EN_TRANSITO' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $item->status === 'ALMACENADO' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $item->status === 'DESPACHADO' ? 'bg-gray-100 text-gray-800' : '' }}">
                                        {{ str_replace('_', ' ', $item->status) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Summary -->
            <div class="mt-6 pt-6 border-t border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600">Total Ítems</p>
                        <p class="text-2xl font-bold text-blue-900">{{ $manifest->cargoItems->count() }}</p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600">Almacenados</p>
                        <p class="text-2xl font-bold text-green-900">
                            {{ $manifest->cargoItems->where('status', 'ALMACENADO')->count() }}
                        </p>
                    </div>
                    <div class="bg-yellow-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600">En Tránsito</p>
                        <p class="text-2xl font-bold text-yellow-900">
                            {{ $manifest->cargoItems->where('status', 'EN_TRANSITO')->count() }}
                        </p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600">Despachados</p>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ $manifest->cargoItems->where('status', 'DESPACHADO')->count() }}
                        </p>
                    </div>
                </div>
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No hay ítems de carga</h3>
                <p class="mt-1 text-sm text-gray-500">Comience agregando ítems de carga a este manifiesto.</p>
                <div class="mt-6">
                    <a href="{{ route('cargo.item.create', ['manifest_id' => $manifest->id]) }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        + Agregar Primer Ítem
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
