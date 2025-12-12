@extends('layouts.app')

@section('title', 'Registrar Ítem de Carga')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Registrar Ítem de Carga</h2>
            <a href="{{ route('cargo.manifest.show', $manifestId ?? request('manifest_id')) }}" 
               class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded transition-colors">
                Volver al Manifiesto
            </a>
        </div>

        <form action="{{ route('cargo.item.store') }}" 
              method="POST" 
              x-data="cargoItemForm()">
            @csrf

            <input type="hidden" name="manifest_id" value="{{ $manifestId ?? request('manifest_id') }}">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Item Number -->
                <div>
                    <label for="item_number" class="block text-sm font-medium text-gray-700 mb-2">
                        Número de Ítem <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="item_number" 
                           id="item_number" 
                           required
                           value="{{ old('item_number') }}"
                           maxlength="50"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('item_number') border-red-500 @enderror"
                           placeholder="Ej: ITEM-001">
                    @error('item_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Cargo Type -->
                <div>
                    <label for="cargo_type" class="block text-sm font-medium text-gray-700 mb-2">
                        Tipo de Carga <span class="text-red-500">*</span>
                    </label>
                    <select name="cargo_type" 
                            id="cargo_type" 
                            required
                            x-model="cargoType"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('cargo_type') border-red-500 @enderror">
                        <option value="">Seleccione un tipo</option>
                        <option value="CONTENEDOR" {{ old('cargo_type') == 'CONTENEDOR' ? 'selected' : '' }}>Contenedor</option>
                        <option value="GRANEL" {{ old('cargo_type') == 'GRANEL' ? 'selected' : '' }}>Granel</option>
                        <option value="CARGA_GENERAL" {{ old('cargo_type') == 'CARGA_GENERAL' ? 'selected' : '' }}>Carga General</option>
                    </select>
                    @error('cargo_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div class="col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Descripción <span class="text-red-500">*</span>
                    </label>
                    <textarea name="description" 
                              id="description" 
                              required
                              rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('description') border-red-500 @enderror"
                              placeholder="Describa la mercancía">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Container Number (conditional) -->
                <div x-show="cargoType === 'CONTENEDOR'">
                    <label for="container_number" class="block text-sm font-medium text-gray-700 mb-2">
                        Número de Contenedor
                    </label>
                    <input type="text" 
                           name="container_number" 
                           id="container_number" 
                           value="{{ old('container_number') }}"
                           maxlength="20"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('container_number') border-red-500 @enderror"
                           placeholder="Ej: ABCD1234567">
                    @error('container_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Formato ISO 6346</p>
                </div>

                <!-- Seal Number (conditional) -->
                <div x-show="cargoType === 'CONTENEDOR'">
                    <label for="seal_number" class="block text-sm font-medium text-gray-700 mb-2">
                        Número de Precinto
                    </label>
                    <input type="text" 
                           name="seal_number" 
                           id="seal_number" 
                           value="{{ old('seal_number') }}"
                           maxlength="50"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('seal_number') border-red-500 @enderror"
                           placeholder="Ej: SEAL123456">
                    @error('seal_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Weight -->
                <div>
                    <label for="weight_kg" class="block text-sm font-medium text-gray-700 mb-2">
                        Peso (kg) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           name="weight_kg" 
                           id="weight_kg" 
                           required
                           min="0"
                           step="0.01"
                           value="{{ old('weight_kg', 0) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('weight_kg') border-red-500 @enderror"
                           placeholder="0.00">
                    @error('weight_kg')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Volume -->
                <div>
                    <label for="volume_m3" class="block text-sm font-medium text-gray-700 mb-2">
                        Volumen (m³)
                    </label>
                    <input type="number" 
                           name="volume_m3" 
                           id="volume_m3" 
                           min="0"
                           step="0.01"
                           value="{{ old('volume_m3') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('volume_m3') border-red-500 @enderror"
                           placeholder="0.00">
                    @error('volume_m3')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- B/L Number -->
                <div>
                    <label for="bl_number" class="block text-sm font-medium text-gray-700 mb-2">
                        Número de B/L
                    </label>
                    <input type="text" 
                           name="bl_number" 
                           id="bl_number" 
                           value="{{ old('bl_number') }}"
                           maxlength="50"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('bl_number') border-red-500 @enderror"
                           placeholder="Ej: BL-2025-001">
                    @error('bl_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Bill of Lading (Conocimiento de Embarque)</p>
                </div>

                <!-- Consignee -->
                <div>
                    <label for="consignee" class="block text-sm font-medium text-gray-700 mb-2">
                        Consignatario
                    </label>
                    <input type="text" 
                           name="consignee" 
                           id="consignee" 
                           value="{{ old('consignee') }}"
                           maxlength="255"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('consignee') border-red-500 @enderror"
                           placeholder="Nombre del consignatario">
                    @error('consignee')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Yard Location (optional) -->
                <div>
                    <label for="yard_location_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Ubicación en Patio
                    </label>
                    <select name="yard_location_id" 
                            id="yard_location_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('yard_location_id') border-red-500 @enderror">
                        <option value="">Sin asignar</option>
                        @foreach(\App\Models\YardLocation::where('active', true)->where('occupied', false)->orderBy('zone_code')->get() as $location)
                            <option value="{{ $location->id }}" {{ old('yard_location_id') == $location->id ? 'selected' : '' }}>
                                {{ $location->zone_code }}
                                @if($location->block_code) - Bloque {{ $location->block_code }} @endif
                                @if($location->row_code) - Fila {{ $location->row_code }} @endif
                                @if($location->tier) - Nivel {{ $location->tier }} @endif
                            </option>
                        @endforeach
                    </select>
                    @error('yard_location_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                        Estado <span class="text-red-500">*</span>
                    </label>
                    <select name="status" 
                            id="status" 
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('status') border-red-500 @enderror">
                        <option value="">Seleccione un estado</option>
                        <option value="EN_TRANSITO" {{ old('status') == 'EN_TRANSITO' ? 'selected' : '' }}>En Tránsito</option>
                        <option value="ALMACENADO" {{ old('status', 'EN_TRANSITO') == 'ALMACENADO' ? 'selected' : '' }}>Almacenado</option>
                        <option value="DESPACHADO" {{ old('status') == 'DESPACHADO' ? 'selected' : '' }}>Despachado</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-4 mt-6 pt-6 border-t border-gray-200">
                <a href="{{ route('cargo.manifest.show', $manifestId ?? request('manifest_id')) }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancelar
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                    Registrar Ítem
                </button>
            </div>
        </form>
    </div>

    <!-- Help Section -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 class="font-semibold text-blue-900 mb-2">Información</h3>
        <ul class="text-sm text-blue-800 space-y-1">
            <li><strong>Contenedor:</strong> Unidad de carga estandarizada (20', 40', etc.)</li>
            <li><strong>Granel:</strong> Carga suelta (minerales, granos, líquidos)</li>
            <li><strong>Carga General:</strong> Mercancía no contenedorizada ni a granel</li>
            <li><strong>B/L:</strong> Conocimiento de Embarque - documento que acredita la propiedad de la carga</li>
        </ul>
    </div>
</div>

<script>
function cargoItemForm() {
    return {
        cargoType: '{{ old('cargo_type', '') }}',
    };
}
</script>
@endsection
