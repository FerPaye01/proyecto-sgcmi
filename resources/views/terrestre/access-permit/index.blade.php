@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Permisos de Acceso</h1>
        <p class="text-gray-600 mt-2">Gestión de permisos de entrada y salida</p>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" action="{{ route('access-permit.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Permiso</label>
                <select name="permit_type" class="w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">Todos</option>
                    <option value="SALIDA" {{ request('permit_type') === 'SALIDA' ? 'selected' : '' }}>Salida</option>
                    <option value="INGRESO" {{ request('permit_type') === 'INGRESO' ? 'selected' : '' }}>Ingreso</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                <select name="status" class="w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">Todos</option>
                    <option value="PENDIENTE" {{ request('status') === 'PENDIENTE' ? 'selected' : '' }}>Pendiente</option>
                    <option value="USADO" {{ request('status') === 'USADO' ? 'selected' : '' }}>Usado</option>
                    <option value="VENCIDO" {{ request('status') === 'VENCIDO' ? 'selected' : '' }}>Vencido</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ID Camión</label>
                <input type="number" name="truck_id" value="{{ request('truck_id') }}" 
                       class="w-full border-gray-300 rounded-md shadow-sm" placeholder="ID del camión">
            </div>

            <div class="flex items-end">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 mr-2">
                    Filtrar
                </button>
                <a href="{{ route('access-permit.index') }}" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-400">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Lista de Permisos</h2>
            @can('create', App\Models\AccessPermit::class)
                <a href="{{ route('access-permit.create') }}" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                    + Crear Permiso
                </a>
            @endcan
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pase Digital</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Carga</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Autorizado Por</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha Autorización</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($permits as $permit)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $permit->id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $permit->digitalPass->pass_code ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full {{ $permit->permit_type === 'SALIDA' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                    {{ $permit->permit_type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($permit->cargoItem)
                                    {{ $permit->cargoItem->container_number ?? $permit->cargoItem->item_number }}
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($permit->authorizer)
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        <span class="font-medium">{{ $permit->authorizer->email }}</span>
                                    </div>
                                @else
                                    <span class="text-gray-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $permit->authorized_at?->format('d/m/Y H:i') ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    {{ $permit->status === 'PENDIENTE' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $permit->status === 'USADO' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $permit->status === 'VENCIDO' ? 'bg-gray-100 text-gray-800' : '' }}">
                                    {{ $permit->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                No se encontraron permisos de acceso
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $permits->links() }}
        </div>
    </div>
</div>
@endsection
