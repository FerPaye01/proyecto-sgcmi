@extends('layouts.app')

@section('title', 'Registrar Movimiento de Carga')

@section('content')
<div class="max-w-4xl mx-auto" x-data="movementRegisterData()">
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Registrar Movimiento de Carga</h2>
            <a href="{{ route('yard.map') }}" 
               class="text-blue-600 hover:text-blue-800 transition-colors">
                ← Volver al Mapa
            </a>
        </div>

        @if ($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Errores de validación:</h3>
                        <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('yard.move-cargo') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Cargo Item Selection -->
            <div>
                <label for="cargo_item_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Ítem de Carga <span class="text-red-500">*</span>
                </label>
                <select id="cargo_item_id" 
                        name="cargo_item_id" 
                        x-model="selectedCargoItem"
                        @change="loadCargoDetails()"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Seleccione un ítem de carga</option>
                    @foreach($cargoItems as $item)
                        <option value="{{ $item->id }}" 
                                data-current-location="{{ $item->yard_location_id }}"
                                data-container="{{ $item->container_number }}"
                                data-description="{{ $item->description }}">
                            {{ $item->container_number ?? $item->item_number }} - {{ $item->description }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-sm text-gray-500">Seleccione el ítem de carga que desea mover</p>
            </div>

            <!-- Current Location Display -->
            <div x-show="selectedCargoItem" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-blue-900 mb-2">Ubicación Actual</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-blue-700">Contenedor/Ítem:</p>
                        <p class="text-sm font-semibold text-blue-900" x-text="currentCargoInfo.container"></p>
                    </div>
                    <div>
                        <p class="text-xs text-blue-700">Descripción:</p>
                        <p class="text-sm font-semibold text-blue-900" x-text="currentCargoInfo.description"></p>
                    </div>
                    <div>
                        <p class="text-xs text-blue-700">Ubicación:</p>
                        <p class="text-sm font-semibold text-blue-900" x-text="currentCargoInfo.location"></p>
                    </div>
                </div>
            </div>

            <!-- Movement Type -->
            <div>
                <label for="movement_type" class="block text-sm font-medium text-gray-700 mb-2">
                    Tipo de Movimiento <span class="text-red-500">*</span>
                </label>
                <select id="movement_type" 
                        name="movement_type" 
                        x-model="movementType"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Seleccione tipo</option>
                    <option value="TRACCION">Tracción (Movimiento interno)</option>
                    <option value="TRANSFERENCIA">Transferencia (Cambio de zona)</option>
                    <option value="MUELLE_A_PATIO">Muelle a Patio</option>
                    <option value="PATIO_A_MUELLE">Patio a Muelle</option>
                    <option value="DESPACHO">Despacho (Salida)</option>
                </select>
            </div>

            <!-- Origin Location -->
            <div x-show="movementType && movementType !== 'DESPACHO'">
                <label for="origin_location_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Ubicación de Origen <span class="text-red-500">*</span>
                </label>
                <select id="origin_location_id" 
                        name="origin_location_id" 
                        :required="movementType && movementType !== 'DESPACHO'"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Seleccione ubicación de origen</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}">
                            {{ $location->zone_code }}
                            @if($location->block_code) - Bloque {{ $location->block_code }} @endif
                            @if($location->row_code) - Fila {{ $location->row_code }} @endif
                            @if($location->tier) - Nivel {{ $location->tier }} @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Destination Location -->
            <div x-show="movementType && movementType !== 'DESPACHO'">
                <label for="destination_location_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Ubicación de Destino <span class="text-red-500">*</span>
                </label>
                <select id="destination_location_id" 
                        name="destination_location_id" 
                        :required="movementType && movementType !== 'DESPACHO'"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Seleccione ubicación de destino</option>
                    @foreach($availableLocations as $location)
                        <option value="{{ $location->id }}">
                            {{ $location->zone_code }}
                            @if($location->block_code) - Bloque {{ $location->block_code }} @endif
                            @if($location->row_code) - Fila {{ $location->row_code }} @endif
                            @if($location->tier) - Nivel {{ $location->tier }} @endif
                            ({{ $location->location_type }})
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-sm text-gray-500">Solo se muestran ubicaciones disponibles</p>
            </div>

            <!-- Movement Date/Time -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="movement_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Fecha de Movimiento <span class="text-red-500">*</span>
                    </label>
                    <input type="date" 
                           id="movement_date" 
                           name="movement_date" 
                           value="{{ old('movement_date', date('Y-m-d')) }}"
                           required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div>
                    <label for="movement_time" class="block text-sm font-medium text-gray-700 mb-2">
                        Hora de Movimiento <span class="text-red-500">*</span>
                    </label>
                    <input type="time" 
                           id="movement_time" 
                           name="movement_time" 
                           value="{{ old('movement_time', date('H:i')) }}"
                           required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>

            <!-- Equipment Used -->
            <div>
                <label for="equipment_used" class="block text-sm font-medium text-gray-700 mb-2">
                    Equipo Utilizado
                </label>
                <input type="text" 
                       id="equipment_used" 
                       name="equipment_used" 
                       value="{{ old('equipment_used') }}"
                       placeholder="Ej: Grúa RTG-01, Reach Stacker RS-03"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Operator -->
            <div>
                <label for="operator_name" class="block text-sm font-medium text-gray-700 mb-2">
                    Operador <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       id="operator_name" 
                       name="operator_name" 
                       value="{{ old('operator_name', auth()->user()->name) }}"
                       required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Observations -->
            <div>
                <label for="observations" class="block text-sm font-medium text-gray-700 mb-2">
                    Observaciones
                </label>
                <textarea id="observations" 
                          name="observations" 
                          rows="3"
                          placeholder="Ingrese cualquier observación relevante sobre el movimiento"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ old('observations') }}</textarea>
            </div>

            <!-- Seal Verification (for containers) -->
            <div x-show="currentCargoInfo.container">
                <label class="flex items-center space-x-2">
                    <input type="checkbox" 
                           name="seal_verified" 
                           value="1"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">Precinto verificado</span>
                </label>
                
                <div class="mt-2">
                    <label for="seal_number" class="block text-sm font-medium text-gray-700 mb-2">
                        Número de Precinto
                    </label>
                    <input type="text" 
                           id="seal_number" 
                           name="seal_number" 
                           value="{{ old('seal_number') }}"
                           placeholder="Ingrese número de precinto"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                <a href="{{ route('yard.map') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancelar
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                    Registrar Movimiento
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function movementRegisterData() {
    return {
        selectedCargoItem: '',
        movementType: '',
        currentCargoInfo: {
            container: '',
            description: '',
            location: ''
        },
        
        loadCargoDetails() {
            if (!this.selectedCargoItem) {
                this.currentCargoInfo = { container: '', description: '', location: '' };
                return;
            }
            
            const select = document.getElementById('cargo_item_id');
            const option = select.options[select.selectedIndex];
            
            this.currentCargoInfo = {
                container: option.dataset.container || 'N/A',
                description: option.dataset.description || 'N/A',
                location: this.getLocationName(option.dataset.currentLocation)
            };
            
            // Auto-select origin location
            const originSelect = document.getElementById('origin_location_id');
            if (originSelect && option.dataset.currentLocation) {
                originSelect.value = option.dataset.currentLocation;
            }
        },
        
        getLocationName(locationId) {
            if (!locationId) return 'Sin ubicación asignada';
            
            const locations = @json($locations ?? []);
            const location = locations.find(l => l.id == locationId);
            
            if (!location) return 'Ubicación desconocida';
            
            let name = location.zone_code;
            if (location.block_code) name += ` - Bloque ${location.block_code}`;
            if (location.row_code) name += ` - Fila ${location.row_code}`;
            if (location.tier) name += ` - Nivel ${location.tier}`;
            
            return name;
        }
    };
}
</script>
@endsection