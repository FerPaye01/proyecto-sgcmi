@extends('layouts.app')

@section('title', 'Generar Pase Digital')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Generar Pase Digital</h1>
        <a href="{{ route('digital-pass.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
            ← Volver al Listado
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6 max-w-2xl">
        <form method="POST" action="{{ route('digital-pass.generate') }}">
            @csrf

            <!-- Tipo de Pase -->
            <div class="mb-4">
                <label for="pass_type" class="block text-sm font-medium text-gray-700 mb-1">
                    Tipo de Pase <span class="text-red-500">*</span>
                </label>
                <select 
                    id="pass_type" 
                    name="pass_type" 
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    x-data="{ type: '{{ old('pass_type', 'PERSONAL') }}' }"
                    x-model="type"
                    @change="$dispatch('pass-type-changed', { type: type })"
                >
                    <option value="PERSONAL">Personal</option>
                    <option value="VEHICULAR">Vehicular</option>
                </select>
                @error('pass_type')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Nombre del Titular -->
            <div class="mb-4">
                <label for="holder_name" class="block text-sm font-medium text-gray-700 mb-1">
                    Nombre del Titular <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    id="holder_name" 
                    name="holder_name" 
                    value="{{ old('holder_name') }}"
                    required
                    maxlength="255"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Ingrese el nombre completo"
                >
                @error('holder_name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- DNI del Titular -->
            <div class="mb-4">
                <label for="holder_dni" class="block text-sm font-medium text-gray-700 mb-1">
                    DNI del Titular <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    id="holder_dni" 
                    name="holder_dni" 
                    value="{{ old('holder_dni') }}"
                    required
                    maxlength="20"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Ingrese el DNI"
                >
                @error('holder_dni')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Vehículo (solo para pases vehiculares) -->
            <div 
                class="mb-4"
                x-data="{ passType: '{{ old('pass_type', 'PERSONAL') }}' }"
                x-show="passType === 'VEHICULAR'"
                @pass-type-changed.window="passType = $event.detail.type"
            >
                <label for="truck_id" class="block text-sm font-medium text-gray-700 mb-1">
                    Vehículo <span class="text-red-500" x-show="passType === 'VEHICULAR'">*</span>
                </label>
                <select 
                    id="truck_id" 
                    name="truck_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">Seleccione un vehículo</option>
                    @foreach($trucks as $truck)
                        <option value="{{ $truck->id }}" {{ old('truck_id') == $truck->id ? 'selected' : '' }}>
                            {{ $truck->placa }} - {{ $truck->company->nombre ?? 'Sin empresa' }}
                        </option>
                    @endforeach
                </select>
                @error('truck_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Fecha de Inicio de Validez -->
            <div class="mb-4">
                <label for="valid_from" class="block text-sm font-medium text-gray-700 mb-1">
                    Válido Desde <span class="text-red-500">*</span>
                </label>
                <input 
                    type="datetime-local" 
                    id="valid_from" 
                    name="valid_from" 
                    value="{{ old('valid_from', now()->format('Y-m-d\TH:i')) }}"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                @error('valid_from')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Fecha de Fin de Validez -->
            <div class="mb-4">
                <label for="valid_until" class="block text-sm font-medium text-gray-700 mb-1">
                    Válido Hasta <span class="text-red-500">*</span>
                </label>
                <input 
                    type="datetime-local" 
                    id="valid_until" 
                    name="valid_until" 
                    value="{{ old('valid_until', now()->addDays(30)->format('Y-m-d\TH:i')) }}"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                @error('valid_until')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Botones -->
            <div class="flex justify-end space-x-3 mt-6">
                <a href="{{ route('digital-pass.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-md font-medium">
                    Cancelar
                </a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md font-medium">
                    Generar Pase Digital
                </button>
            </div>
        </form>
    </div>

    <!-- Información adicional -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6 max-w-2xl">
        <h3 class="font-semibold text-blue-900 mb-2">ℹ️ Información sobre Pases Digitales</h3>
        <ul class="text-sm text-blue-800 space-y-1">
            <li>• Los pases <strong>PERSONAL</strong> son para trabajadores portuarios sin vehículo</li>
            <li>• Los pases <strong>VEHICULAR</strong> son para conductores con vehículo asignado</li>
            <li>• El código QR se genera automáticamente al crear el pase</li>
            <li>• Los pases pueden ser revocados en cualquier momento desde el listado</li>
            <li>• La validez del pase se verifica automáticamente al escanear el QR</li>
        </ul>
    </div>
</div>
@endsection
