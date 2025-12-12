@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Crear Permiso de Acceso</h1>
        <p class="text-gray-600 mt-2">Autorizar entrada o salida de vehículos</p>
    </div>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-md p-6">
        <form method="POST" action="{{ route('access-permit.store') }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="digital_pass_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Pase Digital <span class="text-red-500">*</span>
                    </label>
                    <select name="digital_pass_id" id="digital_pass_id" required
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Seleccione un pase digital</option>
                        @foreach($digitalPasses as $pass)
                            <option value="{{ $pass->id }}" {{ old('digital_pass_id') == $pass->id ? 'selected' : '' }}>
                                {{ $pass->pass_code }} - {{ $pass->holder_name }}
                                @if($pass->truck)
                                    (Camión ID: {{ $pass->truck_id }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('digital_pass_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="permit_type" class="block text-sm font-medium text-gray-700 mb-2">
                        Tipo de Permiso <span class="text-red-500">*</span>
                    </label>
                    <select name="permit_type" id="permit_type" required
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Seleccione tipo</option>
                        <option value="SALIDA" {{ old('permit_type') === 'SALIDA' ? 'selected' : '' }}>Permiso de Salida</option>
                        <option value="INGRESO" {{ old('permit_type') === 'INGRESO' ? 'selected' : '' }}>Autorización de Ingreso</option>
                    </select>
                    @error('permit_type')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="cargo_item_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Ítem de Carga (Opcional)
                    </label>
                    <select name="cargo_item_id" id="cargo_item_id"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Sin carga asociada</option>
                        @foreach($cargoItems as $item)
                            <option value="{{ $item->id }}" {{ old('cargo_item_id') == $item->id ? 'selected' : '' }}>
                                @if($item->container_number)
                                    Contenedor: {{ $item->container_number }}
                                @else
                                    {{ $item->item_number }} - {{ $item->description }}
                                @endif
                                (Manifiesto: {{ $item->manifest->manifest_number ?? 'N/A' }})
                            </option>
                        @endforeach
                    </select>
                    @error('cargo_item_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-sm text-gray-500 mt-1">
                        Vincule el permiso a un ítem de carga específico si aplica
                    </p>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Autorizado Por
                    </label>
                    <div class="w-full border-gray-300 bg-gray-100 rounded-md shadow-sm px-3 py-2 text-gray-700">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span class="font-medium">{{ auth()->user()->email }}</span>
                            <span class="ml-2 text-gray-500">({{ auth()->user()->full_name }})</span>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">
                        El permiso será autorizado automáticamente por el usuario actual
                    </p>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('access-permit.index') }}" 
                   class="bg-gray-300 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-400">
                    Cancelar
                </a>
                <button type="submit" 
                        class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">
                    Crear Permiso
                </button>
            </div>
        </form>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
        <h3 class="font-semibold text-blue-900 mb-2">Información sobre Permisos</h3>
        <ul class="text-sm text-blue-800 space-y-1">
            <li><strong>Permiso de Salida:</strong> Requerido para que un vehículo salga del terminal con carga</li>
            <li><strong>Autorización de Ingreso:</strong> Requerida para que un vehículo ingrese al terminal</li>
            <li><strong>Nota de Embarque (B/L):</strong> Debe estar registrada en el ítem de carga para salidas</li>
        </ul>
    </div>
</div>
@endsection
