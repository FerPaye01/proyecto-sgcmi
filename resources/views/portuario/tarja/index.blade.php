@extends('layouts.app')

@section('title', 'Tarja Notes')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Tarja Notes</h1>
        <p class="mt-2 text-sm text-gray-600">Physical verification of cargo</p>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    <!-- Filters -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <form method="GET" action="{{ route('tarja.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Vessel Call Filter -->
                <div>
                    <label for="vessel_call_id" class="block text-sm font-medium text-gray-700">Vessel Call</label>
                    <select name="vessel_call_id" id="vessel_call_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Vessels</option>
                        @foreach($vesselCalls as $vesselCall)
                            <option value="{{ $vesselCall->id }}" {{ request('vessel_call_id') == $vesselCall->id ? 'selected' : '' }}>
                                {{ $vesselCall->vessel->name ?? 'N/A' }} - {{ $vesselCall->eta?->format('Y-m-d') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Condition Filter -->
                <div>
                    <label for="condition" class="block text-sm font-medium text-gray-700">Condition</label>
                    <select name="condition" id="condition" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Conditions</option>
                        <option value="BUENO" {{ request('condition') == 'BUENO' ? 'selected' : '' }}>BUENO</option>
                        <option value="DAÑADO" {{ request('condition') == 'DAÑADO' ? 'selected' : '' }}>DAÑADO</option>
                        <option value="FALTANTE" {{ request('condition') == 'FALTANTE' ? 'selected' : '' }}>FALTANTE</option>
                    </select>
                </div>

                <!-- Inspector Filter -->
                <div>
                    <label for="inspector_name" class="block text-sm font-medium text-gray-700">Inspector</label>
                    <input type="text" name="inspector_name" id="inspector_name" value="{{ request('inspector_name') }}" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                           placeholder="Inspector name">
                </div>

                <!-- Date From -->
                <div>
                    <label for="fecha_desde" class="block text-sm font-medium text-gray-700">Date From</label>
                    <input type="date" name="fecha_desde" id="fecha_desde" value="{{ request('fecha_desde') }}" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <!-- Date To -->
                <div>
                    <label for="fecha_hasta" class="block text-sm font-medium text-gray-700">Date To</label>
                    <input type="date" name="fecha_hasta" id="fecha_hasta" value="{{ request('fecha_hasta') }}" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <div class="flex justify-end space-x-2">
                <a href="{{ route('tarja.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                    Clear Filters
                </a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Action Buttons -->
    <div class="mb-4 flex justify-between items-center">
        <div class="text-sm text-gray-600">
            Showing {{ $tarjaNotes->firstItem() ?? 0 }} to {{ $tarjaNotes->lastItem() ?? 0 }} of {{ $tarjaNotes->total() }} tarja notes
        </div>
        <a href="{{ route('tarja.create') }}" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
            + New Tarja Note
        </a>
    </div>

    <!-- Tarja Notes Table -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarja Number</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cargo Item</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vessel</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inspector</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Condition</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Photos</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($tarjaNotes as $tarjaNote)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $tarjaNote->tarja_number }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $tarjaNote->tarja_date?->format('Y-m-d H:i') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <div>{{ $tarjaNote->cargoItem->item_number ?? 'N/A' }}</div>
                            <div class="text-xs text-gray-400">{{ $tarjaNote->cargoItem->description ?? '' }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $tarjaNote->cargoItem->manifest->vesselCall->vessel->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $tarjaNote->inspector_name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($tarjaNote->condition === 'BUENO')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    BUENO
                                </span>
                            @elseif($tarjaNote->condition === 'DAÑADO')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    DAÑADO
                                </span>
                            @elseif($tarjaNote->condition === 'FALTANTE')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    FALTANTE
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($tarjaNote->photos && count($tarjaNote->photos) > 0)
                                <span class="text-blue-600">{{ count($tarjaNote->photos) }} photo(s)</span>
                            @else
                                <span class="text-gray-400">No photos</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                            No tarja notes found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $tarjaNotes->links() }}
    </div>
</div>
@endsection
