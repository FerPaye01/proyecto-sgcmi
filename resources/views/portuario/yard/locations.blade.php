@extends('layouts.app')

@section('title', 'Ubicaciones del Patio')

@section('content')
<div class="max-w-7xl mx-auto" x-data="yardLocationsData()">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold text-gray-800">Ubicaciones del Patio</h2>
            <div class="flex space-x-2">
                <a href="{{ route('yard.map') }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition-colors">
                    Ver Mapa
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Zona</label>
                <select x-model="filters.zone" 
                        @change="applyFilters()"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">Todas</option>
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
                    <option value="">Todos</option>
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

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Activo</label>
                <select x-model="filters.active" 
                        @change="applyFilters()"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos</option>
                    <option value="true">Activo</option>
                    <option value="false">Inactivo</option>
                </select>
            </div>

            <div class="flex items-end">
                <button @click="clearFilters()" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Limpiar
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-600">Total</p>
            <p class="text-3xl font-bold text-gray-900" x-text="stats.total"></p>
        </div>
        <div class="bg-green-50 rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-600">Disponibles</p>
            <p class="text-3xl font-bold text-green-900" x-text="stats.available"></p>
        </div>
        <div class="bg-red-50 rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-600">Ocupadas</p>
            <p class="text-3xl font-bold text-red-900" x-text="stats.occupied"></p>
        </div>
        <div class="bg-blue-50 rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-600">Capacidad TEU</p>
            <p class="text-3xl font-bold text-blue-900" x-text="stats.totalCapacity"></p>
        </div>
    </div>

    <!-- Locations Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th @click="sortBy('zone_code')" 
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                            Zona
                            <span x-show="sortField === 'zone_code'" x-text="sortDirection === 'asc' ? '↑' : '↓'"></span>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Bloque
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Fila
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Nivel
                        </th>
                        <th @click="sortBy('location_type')" 
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                            Tipo
                            <span x-show="sortField === 'location_type'" x-text="sortDirection === 'asc' ? '↑' : '↓'"></span>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Capacidad (TEU)
                        </th>
                        <th @click="sortBy('occupied')" 
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                            Estado
                            <span x-show="sortField === 'occupied'" x-text="sortDirection === 'asc' ? '↑' : '↓'"></span>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Activo
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="location in paginatedLocations" :key="location.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="location.zone_code"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="location.block_code || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="location.row_code || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="location.tier || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full"
                                      :class="{
                                          'bg-blue-100 text-blue-800': location.location_type === 'CONTENEDOR',
                                          'bg-green-100 text-green-800': location.location_type === 'SILO',
                                          'bg-purple-100 text-purple-800': location.location_type === 'ALMACEN',
                                          'bg-yellow-100 text-yellow-800': location.location_type === 'LOSA'
                                      }"
                                      x-text="location.location_type"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="location.capacity_teu || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full"
                                      :class="location.occupied ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'"
                                      x-text="location.occupied ? 'Ocupado' : 'Disponible'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full"
                                      :class="location.active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                      x-text="location.active ? 'Sí' : 'No'"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- No Results -->
        <div x-show="filteredLocations.length === 0" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No se encontraron ubicaciones</h3>
            <p class="mt-1 text-sm text-gray-500">Intente ajustar los filtros.</p>
        </div>

        <!-- Pagination -->
        <div x-show="filteredLocations.length > 0" class="bg-gray-50 px-6 py-4 flex items-center justify-between border-t border-gray-200">
            <div class="flex-1 flex justify-between sm:hidden">
                <button @click="prevPage()" 
                        :disabled="currentPage === 1"
                        :class="currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white">
                    Anterior
                </button>
                <button @click="nextPage()" 
                        :disabled="currentPage === totalPages"
                        :class="currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                        class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white">
                    Siguiente
                </button>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Mostrando
                        <span class="font-medium" x-text="startIndex + 1"></span>
                        a
                        <span class="font-medium" x-text="Math.min(endIndex, filteredLocations.length)"></span>
                        de
                        <span class="font-medium" x-text="filteredLocations.length"></span>
                        resultados
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                        <button @click="prevPage()" 
                                :disabled="currentPage === 1"
                                :class="currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'"
                                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500">
                            ←
                        </button>
                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                            Página <span x-text="currentPage"></span> de <span x-text="totalPages"></span>
                        </span>
                        <button @click="nextPage()" 
                                :disabled="currentPage === totalPages"
                                :class="currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'"
                                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500">
                            →
                        </button>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function yardLocationsData() {
    return {
        locations: @json($locations ?? []),
        filteredLocations: [],
        sortField: 'zone_code',
        sortDirection: 'asc',
        currentPage: 1,
        perPage: 20,
        filters: {
            zone: '',
            type: '',
            occupied: '',
            active: ''
        },
        stats: {
            total: 0,
            available: 0,
            occupied: 0,
            totalCapacity: 0
        },
        
        init() {
            this.filteredLocations = this.locations;
            this.sortLocations();
            this.updateStats();
        },
        
        get availableZones() {
            return [...new Set(this.locations.map(l => l.zone_code))].sort();
        },
        
        get totalPages() {
            return Math.ceil(this.filteredLocations.length / this.perPage);
        },
        
        get startIndex() {
            return (this.currentPage - 1) * this.perPage;
        },
        
        get endIndex() {
            return this.startIndex + this.perPage;
        },
        
        get paginatedLocations() {
            return this.filteredLocations.slice(this.startIndex, this.endIndex);
        },
        
        applyFilters() {
            this.filteredLocations = this.locations.filter(location => {
                if (this.filters.zone && location.zone_code !== this.filters.zone) return false;
                if (this.filters.type && location.location_type !== this.filters.type) return false;
                if (this.filters.occupied !== '' && location.occupied.toString() !== this.filters.occupied) return false;
                if (this.filters.active !== '' && location.active.toString() !== this.filters.active) return false;
                return true;
            });
            this.currentPage = 1;
            this.sortLocations();
            this.updateStats();
        },
        
        clearFilters() {
            this.filters = { zone: '', type: '', occupied: '', active: '' };
            this.applyFilters();
        },
        
        sortBy(field) {
            if (this.sortField === field) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = field;
                this.sortDirection = 'asc';
            }
            this.sortLocations();
        },
        
        sortLocations() {
            this.filteredLocations.sort((a, b) => {
                let aVal = a[this.sortField];
                let bVal = b[this.sortField];
                
                if (typeof aVal === 'boolean') {
                    aVal = aVal ? 1 : 0;
                    bVal = bVal ? 1 : 0;
                }
                
                if (aVal < bVal) return this.sortDirection === 'asc' ? -1 : 1;
                if (aVal > bVal) return this.sortDirection === 'asc' ? 1 : -1;
                return 0;
            });
        },
        
        updateStats() {
            this.stats.total = this.filteredLocations.length;
            this.stats.available = this.filteredLocations.filter(l => !l.occupied && l.active).length;
            this.stats.occupied = this.filteredLocations.filter(l => l.occupied && l.active).length;
            this.stats.totalCapacity = this.filteredLocations
                .reduce((sum, l) => sum + (l.capacity_teu || 0), 0);
        },
        
        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
            }
        },
        
        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
            }
        }
    };
}
</script>
@endsection
