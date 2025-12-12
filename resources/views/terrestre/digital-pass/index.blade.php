@extends('layouts.app')

@section('title', 'Pases Digitales')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Gestión de Pases Digitales</h1>
        <a href="{{ route('digital-pass.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
            + Generar Nuevo Pase
        </a>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" action="{{ route('digital-pass.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <!-- Tipo de Pase -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Pase</label>
                <select name="pass_type" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">Todos</option>
                    <option value="PERSONAL" {{ request('pass_type') == 'PERSONAL' ? 'selected' : '' }}>Personal</option>
                    <option value="VEHICULAR" {{ request('pass_type') == 'VEHICULAR' ? 'selected' : '' }}>Vehicular</option>
                </select>
            </div>

            <!-- Estado -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">Todos</option>
                    <option value="ACTIVO" {{ request('status') == 'ACTIVO' ? 'selected' : '' }}>Activo</option>
                    <option value="VENCIDO" {{ request('status') == 'VENCIDO' ? 'selected' : '' }}>Vencido</option>
                    <option value="REVOCADO" {{ request('status') == 'REVOCADO' ? 'selected' : '' }}>Revocado</option>
                </select>
            </div>

            <!-- Validez -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Validez</label>
                <select name="validity" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">Todos</option>
                    <option value="valid" {{ request('validity') == 'valid' ? 'selected' : '' }}>Válidos</option>
                    <option value="expired" {{ request('validity') == 'expired' ? 'selected' : '' }}>Expirados</option>
                    <option value="active" {{ request('validity') == 'active' ? 'selected' : '' }}>Activos</option>
                </select>
            </div>

            <!-- Nombre del Titular -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Titular</label>
                <input 
                    type="text" 
                    name="holder_name" 
                    value="{{ request('holder_name') }}" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md"
                    placeholder="Buscar por nombre"
                >
            </div>

            <!-- Botón Filtrar -->
            <div class="flex items-end">
                <button type="submit" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md font-medium">
                    Filtrar
                </button>
            </div>
        </form>

        <!-- Filtros adicionales -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
            <!-- DNI -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">DNI</label>
                <input 
                    type="text" 
                    name="holder_dni" 
                    value="{{ request('holder_dni') }}" 
                    form="filter-form"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md"
                    placeholder="Buscar por DNI"
                >
            </div>

            <!-- Código de Pase -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Código de Pase</label>
                <input 
                    type="text" 
                    name="pass_code" 
                    value="{{ request('pass_code') }}" 
                    form="filter-form"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md"
                    placeholder="Buscar por código"
                >
            </div>

            <!-- Botón Limpiar -->
            <div class="flex items-end">
                <a href="{{ route('digital-pass.index') }}" class="w-full bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md font-medium text-center">
                    Limpiar Filtros
                </a>
            </div>
        </div>
    </div>

    <!-- Estadísticas Rápidas -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total de Pases</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $digitalPasses->total() }}</p>
                </div>
                <div class="text-3xl">📋</div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Pases Activos</p>
                    <p class="text-2xl font-bold text-green-600">
                        {{ $digitalPasses->where('status', 'ACTIVO')->count() }}
                    </p>
                </div>
                <div class="text-3xl">✓</div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Pases Vencidos</p>
                    <p class="text-2xl font-bold text-yellow-600">
                        {{ $digitalPasses->where('status', 'VENCIDO')->count() }}
                    </p>
                </div>
                <div class="text-3xl">⏰</div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Pases Revocados</p>
                    <p class="text-2xl font-bold text-red-600">
                        {{ $digitalPasses->where('status', 'REVOCADO')->count() }}
                    </p>
                </div>
                <div class="text-3xl">✗</div>
            </div>
        </div>
    </div>

    <!-- Tabla de Pases Digitales -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-blue-900 text-white">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Código</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Tipo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Titular</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">DNI</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Vehículo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Validez</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($digitalPasses as $pass)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm font-mono font-semibold text-gray-900">{{ $pass->pass_code }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($pass->pass_type === 'PERSONAL')
                            <span class="px-2 py-1 text-xs font-semibold rounded bg-purple-100 text-purple-800">👤 Personal</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800">🚛 Vehicular</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $pass->holder_name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $pass->holder_dni }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $pass->truck?->placa ?? '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <div class="text-xs">
                            <div>Desde: {{ $pass->valid_from->format('d/m/Y') }}</div>
                            <div>Hasta: {{ $pass->valid_until->format('d/m/Y') }}</div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($pass->status === 'ACTIVO')
                            <span class="px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-800">✓ Activo</span>
                        @elseif($pass->status === 'VENCIDO')
                            <span class="px-2 py-1 text-xs font-semibold rounded bg-yellow-100 text-yellow-800">⏰ Vencido</span>
                        @elseif($pass->status === 'REVOCADO')
                            <span class="px-2 py-1 text-xs font-semibold rounded bg-red-100 text-red-800">✗ Revocado</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-800">{{ $pass->status }}</span>
                        @endif
                        
                        @if($pass->isValid())
                            <div class="mt-1">
                                <span class="px-2 py-1 text-xs font-semibold rounded bg-green-50 text-green-700">Válido</span>
                            </div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex space-x-2">
                            <a href="{{ route('digital-pass.show', $pass) }}" class="text-blue-600 hover:text-blue-900">
                                Ver
                            </a>
                            @if($pass->status === 'ACTIVO')
                                <form method="POST" action="{{ route('digital-pass.revoke', $pass) }}" class="inline" onsubmit="return confirm('¿Revocar este pase digital?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                        Revocar
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <div class="text-6xl mb-4">📋</div>
                            <p class="text-lg font-medium">No hay pases digitales registrados</p>
                            <p class="text-sm mt-2">Genere un nuevo pase digital para comenzar</p>
                            <a href="{{ route('digital-pass.create') }}" class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                                + Generar Primer Pase
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div class="mt-6">
        {{ $digitalPasses->links() }}
    </div>

    <!-- Acceso Rápido -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-blue-900">🔍 Validar Pase Digital</h3>
                <p class="text-sm text-blue-800 mt-1">Escanee o ingrese el código QR para validar un pase</p>
            </div>
            <a href="{{ route('digital-pass.validate-form') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                Ir a Validación
            </a>
        </div>
    </div>
</div>
@endsection
