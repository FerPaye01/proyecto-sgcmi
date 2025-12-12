@extends('layouts.app')

@section('title', 'Mapa del Patio')

@section('content')
<div class="max-w-7xl mx-auto" x-data="yardMapData()">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold text-gray-800">Mapa del Patio</h2>
            <div class="flex space-x-2">
                <a href="{{ route('yard.available-locations') }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition-colors">
                    Ver Ubicaciones Disponibles
                </a>
                <button @click="refreshMap()" 
                        class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded transition-colors">
                    🔄 Actualizar
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Zona</label>
                <select x-model="filters.zone" 
                        @change="applyFilters()"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">Todas las zonas</option>
                    <template x-for="zone in availableZones" :key="zone">
                        <option :value="zone" x-text="zone"></option>
                    </template>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                <select x-model="filters.type" 
                        @change="applyFilters()"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos los tipos</option>
                    <option value="CONTENEDOR">Contenedor</option>
                    <option value="SILO">Silo</option>
                    <option value="ALMACEN">Almacén</option>
                    <option value="LOSA">Losa</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                <select x-model="filters.occupied" 
                        @change="applyFilters()"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos</option>
                    <option value="false">Disponible</option>
                    <option value="true">Ocupado</option>
                </select>
            </div>

            <div class="flex items-end">
                <button @click="clearFilters()" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Limpiar Filtros
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-600">Total Ubicaciones</p>
            <p class="text-3xl font-bold text-gray-900" x-text="stats.total"></p>
        </div>
        <div class="bg-green-50 rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-600">Disponibles</p>
            <p class="text-3xl font-bold text-green-900" x-text="stats.available"></p>
            <p class="text-xs text-gray-500" x-text="stats.availablePercent + '%'"></p>
        </div>
        <div class="bg-red-50 rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-600">Ocupadas</p>
            <p class="text-3xl font-bold text-red-900" x-text="stats.occupied"></p>
            <p class="text-xs text-gray-500" x-text="stats.occupiedPercent + '%'"></p>
        </div>
        <div class="bg-blue-50 rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-600">Capacidad TEU</p>
            <p class="text-3xl font-bold text-blue-900" x-text="stats.totalCapacity"></p>
        </div>
    </div>

    <!-- Yard Map Grid -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Vista del Patio</h3>

        <!-- Legend -->
        <div class="flex space-x-4 mb-6 p-4 bg-gray-50 rounded-lg">
            <div class="flex items-center space-x-2">
                <div class="w-6 h-6 bg-green-200 border-2 border-green-500 rounded"></div>
                <span class="text-sm text-gray-700">Disponible</span>
            </div>
            <div class="flex items-center space-x-2">
                <div class="w-6 h-6 bg-red-200 border-2 border-red-500 rounded"></div>
                <span class="text-sm text-gray-700">Ocupado</span>
            </div>
            <div class="flex items-center space-x-2">
                <div class="w-6 h-6 bg-gray-200 border-2 border-gray-400 rounded"></div>
                <span class="text-sm text-gray-700">Inactivo</span>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            <p class="mt-4 text-gray-600">Cargando mapa del patio...</p>
        </div>

        <!-- Yard Grid -->
        <div x-show="!loading" class="space-y-6">
            <template x-for="zone in groupedLocations" :key="zone.name">
                <div class="border border-gray-200 rounded-lg p-4">
                    <h4 class="font-bold text-lg text-gray-800 mb-3" x-text="'Zona: ' + zone.name"></h4>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2">
                        <template x-for="location in zone.locations" :key="location.id">
                            <div @click="selectLocation(location)"
                                 class="relative p-3 rounded-lg border-2 cursor-pointer transition-all hover:shadow-lg"
                                 :class="{
                                     'bg-green-100 border-green-500': !location.occupied && location.active,
                                     'bg-red-100 border-red-500': location.occupied && location.active,
                                     'bg-gray-100 border-gray-400': !location.active,
                                     'ring-2 ring-blue-500': selectedLocation && selectedLocation.id === location.id
                                 }">
                                <div class="text-xs font-semibold text-gray-800" x-text="location.zone_code"></div>
                                <div class="text-xs text-gray-600" x-show="location.block_code" x-text="'B:' + location.block_code"></div>
                                <div class="text-xs text-gray-600" x-show="location.row_code" x-text="'F:' + location.row_code"></div>
                                <div class="text-xs text-gray-600" x-show="location.tier" x-text="'N:' + location.tier"></div>
                                <div class="text-xs font-bold mt-1" 
                                     :class="location.occupied ? 'text-red-700' : 'text-green-700'"
                                     x-text="location.occupied ? '●' : '○'"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        <!-- No Results -->
        <div x-show="!loading && filteredLocations.length === 0" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No se encontraron ubicaciones</h3>
            <p class="mt-1 text-sm text-gray-500">Intente ajustar los filtros.</p>
        </div>
    </div>

    <!-- Location Details Modal -->
    <div x-show="selectedLocation" 
         x-transition
         @click.away="selectedLocation = null"
         class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
         style="display: none;">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Detalles de Ubicación</h3>
                
                <template x-if="selectedLocation">
                    <div class="space-y-3">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Zona</label>
                            <p class="text-lg font-semibold" x-text="selectedLocation.zone_code"></p>
                        </div>
                        
                        <div x-show="selectedLocation.block_code">
                            <label class="text-sm font-medium text-gray-600">Bloque</label>
                            <p class="text-lg font-semibold" x-text="selectedLocation.block_code"></p>
                        </div>
                        
                        <div x-show="selectedLocation.row_code">
                            <label class="text-sm font-medium text-gray-600">Fila</label>
                            <p class="text-lg font-semibold" x-text="selectedLocation.row_code"></p>
                        </div>
                        
                        <div x-show="selectedLocation.tier">
                            <label class="text-sm font-medium text-gray-600">Nivel</label>
                            <p class="text-lg font-semibold" x-text="selectedLocation.tier"></p>
                        </div>
                        
                        <div>
                            <label class="text-sm font-medium text-gray-600">Tipo</label>
                            <p class="text-lg font-semibold" x-text="selectedLocation.location_type"></p>
                        </div>
                        
                        <div x-show="selectedLocation.capacity_teu">
                            <label class="text-sm font-medium text-gray-600">Capacidad (TEU)</label>
                            <p class="text-lg font-semibold" x-text="selectedLocation.capacity_teu"></p>
                        </div>
                        
                        <div>
                            <label class="text-sm font-medium text-gray-600">Estado</label>
                            <p class="text-lg font-semibold" 
                               :class="selectedLocation.occupied ? 'text-red-600' : 'text-green-600'"
                               x-text="selectedLocation.occupied ? 'Ocupado' : 'Disponible'"></p>
                        </div>
                    </div>
                </template>
                
                <div class="mt-6 flex justify-end">
                    <button @click="selectedLocation = null" 
                            class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded transition-colors">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function yardMapData() {
    return {
        locations: @json($locations ?? []),
        filteredLocations: [],
        groupedLocations: [],
        selectedLocation: null,
        loading: false,
        filters: {
            zone: '',
            type: '',
            occupied: ''
        },
        stats: {
            total: 0,
            available: 0,
            occupied: 0,
            availablePercent: 0,
            occupiedPercent: 0,
            totalCapacity: 0
        },
        
        init() {
            this.filteredLocations = this.locations;
            this.updateStats();
            this.groupByZone();
        },
        
        get availableZones() {
            return [...new Set(this.locations.map(l => l.zone_code))].sort();
        },
        
        applyFilters() {
            this.filteredLocations = this.locations.filter(location => {
                if (this.filters.zone && location.zone_code !== this.filters.zone) return false;
                if (this.filters.type && location.location_type !== this.filters.type) return false;
                if (this.filters.occupied !== '' && location.occupied.toString() !== this.filters.occupied) return false;
                return true;
            });
            this.updateStats();
            this.groupByZone();
        },
        
        clearFilters() {
            this.filters = { zone: '', type: '', occupied: '' };
            this.applyFilters();
        },
        
        updateStats() {
            this.stats.total = this.filteredLocations.length;
            this.stats.available = this.filteredLocations.filter(l => !l.occupied && l.active).length;
            this.stats.occupied = this.filteredLocations.filter(l => l.occupied && l.active).length;
            this.stats.availablePercent = this.stats.total > 0 
                ? Math.round((this.stats.available / this.stats.total) * 100) 
                : 0;
            this.stats.occupiedPercent = this.stats.total > 0 
                ? Math.round((this.stats.occupied / this.stats.total) * 100) 
                : 0;
            this.stats.totalCapacity = this.filteredLocations
                .reduce((sum, l) => sum + (l.capacity_teu || 0), 0);
        },
        
        groupByZone() {
            const grouped = {};
            this.filteredLocations.forEach(location => {
                if (!grouped[location.zone_code]) {
                    grouped[location.zone_code] = [];
                }
                grouped[location.zone_code].push(location);
            });
            
            this.groupedLocations = Object.keys(grouped).sort().map(zone => ({
                name: zone,
                locations: grouped[zone]
            }));
        },
        
        selectLocation(location) {
            this.selectedLocation = location;
        },
        
        refreshMap() {
            this.loading = true;
            // Simulate refresh - in production, this would fetch from API
            setTimeout(() => {
                this.loading = false;
                this.applyFilters();
            }, 500);
        }
    };
}
</script>
@endsection
