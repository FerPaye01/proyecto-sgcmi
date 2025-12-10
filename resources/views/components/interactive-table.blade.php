@props([
    'headers' => [],
    'data' => [],
    'searchable' => true,
    'sortable' => true,
    'paginate' => true,
    'perPage' => 10,
    'columnToggle' => true,
    'exportable' => false
])

<div x-data="interactiveTable({
    data: {{ Js::from($data) }},
    headers: {{ Js::from($headers) }},
    perPage: {{ $perPage }},
    searchable: {{ $searchable ? 'true' : 'false' }},
    sortable: {{ $sortable ? 'true' : 'false' }}
})" class="w-full">
    
    <!-- Barra de herramientas -->
    <div class="mb-4 flex flex-wrap gap-4 items-center justify-between">
        <!-- Búsqueda en tiempo real -->
        @if($searchable)
            <div class="flex-1 min-w-[200px] max-w-md">
                <input 
                    type="text" 
                    x-model="searchQuery"
                    @input.debounce.300ms="search()"
                    placeholder="Buscar en la tabla..."
                    class="input-field w-full"
                >
            </div>
        @endif

        <div class="flex gap-2 items-center">
            <!-- Selector de columnas -->
            @if($columnToggle)
                <div class="relative" x-data="{ open: false }">
                    <button 
                        @click="open = !open"
                        type="button"
                        class="btn-secondary flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
                        </svg>
                        Columnas
                    </button>
                    
                    <div 
                        x-show="open"
                        @click.away="open = false"
                        x-transition
                        class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 z-50"
                    >
                        <div class="p-3 max-h-64 overflow-y-auto">
                            <template x-for="(header, index) in headers" :key="index">
                                <label class="flex items-center gap-2 p-2 hover:bg-gray-50 rounded cursor-pointer">
                                    <input 
                                        type="checkbox"
                                        x-model="header.visible"
                                        class="rounded border-gray-300 text-sgcmi-blue-600 focus:ring-sgcmi-blue-500"
                                    >
                                    <span class="text-sm" x-text="header.label"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Selector de filas por página -->
            @if($paginate)
                <select 
                    x-model.number="perPage"
                    @change="currentPage = 1"
                    class="input-field w-auto"
                >
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            @endif
        </div>
    </div>

    <!-- Tabla -->
    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full">
            <thead class="table-header">
                <tr>
                    <template x-for="(header, index) in headers" :key="index">
                        <th 
                            x-show="header.visible"
                            class="px-4 py-3 text-left"
                            :class="{ 'cursor-pointer hover:bg-gray-100': header.sortable }"
                            @click="header.sortable && sort(header.key)"
                        >
                            <div class="flex items-center gap-2">
                                <span x-text="header.label"></span>
                                <template x-if="header.sortable">
                                    <span class="text-gray-400">
                                        <svg 
                                            x-show="sortKey !== header.key" 
                                            class="w-4 h-4" 
                                            fill="none" 
                                            stroke="currentColor" 
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                        </svg>
                                        <svg 
                                            x-show="sortKey === header.key && sortDirection === 'asc'" 
                                            class="w-4 h-4 text-sgcmi-blue-600" 
                                            fill="none" 
                                            stroke="currentColor" 
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                        </svg>
                                        <svg 
                                            x-show="sortKey === header.key && sortDirection === 'desc'" 
                                            class="w-4 h-4 text-sgcmi-blue-600" 
                                            fill="none" 
                                            stroke="currentColor" 
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </span>
                                </template>
                            </div>
                        </th>
                    </template>
                </tr>
            </thead>
            <tbody>
                <template x-for="(row, rowIndex) in paginatedData" :key="rowIndex">
                    <tr class="table-row">
                        <template x-for="(header, colIndex) in headers" :key="colIndex">
                            <td 
                                x-show="header.visible"
                                class="px-4 py-3"
                                x-html="formatCell(row[header.key], header)"
                            ></td>
                        </template>
                    </tr>
                </template>
                
                <!-- Sin resultados -->
                <tr x-show="paginatedData.length === 0">
                    <td :colspan="visibleColumnsCount" class="px-4 py-8 text-center text-gray-500">
                        <span x-show="searchQuery">No se encontraron resultados para "<span x-text="searchQuery"></span>"</span>
                        <span x-show="!searchQuery">No hay datos disponibles</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    @if($paginate)
        <div class="mt-4 flex items-center justify-between">
            <div class="text-sm text-gray-600">
                Mostrando 
                <span x-text="((currentPage - 1) * perPage) + 1"></span>
                a 
                <span x-text="Math.min(currentPage * perPage, filteredData.length)"></span>
                de 
                <span x-text="filteredData.length"></span>
                resultados
            </div>
            
            <div class="flex gap-2">
                <button 
                    @click="currentPage--"
                    :disabled="currentPage === 1"
                    :class="currentPage === 1 ? 'opacity-50 cursor-not-allowed' : ''"
                    class="btn-secondary"
                >
                    Anterior
                </button>
                
                <template x-for="page in totalPages" :key="page">
                    <button 
                        @click="currentPage = page"
                        :class="currentPage === page ? 'btn-primary' : 'btn-secondary'"
                        x-show="showPageButton(page)"
                        x-text="page"
                    ></button>
                </template>
                
                <button 
                    @click="currentPage++"
                    :disabled="currentPage === totalPages"
                    :class="currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : ''"
                    class="btn-secondary"
                >
                    Siguiente
                </button>
            </div>
        </div>
    @endif
</div>

<script>
function interactiveTable(config) {
    return {
        // Datos originales
        originalData: config.data,
        headers: config.headers.map(h => ({
            ...h,
            visible: h.visible !== false,
            sortable: h.sortable !== false
        })),
        
        // Estado de búsqueda y ordenamiento
        searchQuery: '',
        sortKey: null,
        sortDirection: 'asc',
        
        // Paginación
        currentPage: 1,
        perPage: config.perPage,
        
        // Datos procesados
        filteredData: [],
        paginatedData: [],
        
        init() {
            this.filteredData = [...this.originalData];
            this.updatePagination();
        },
        
        // Búsqueda en tiempo real
        search() {
            const query = this.searchQuery.toLowerCase().trim();
            
            if (!query) {
                this.filteredData = [...this.originalData];
            } else {
                this.filteredData = this.originalData.filter(row => {
                    return this.headers.some(header => {
                        if (!header.visible) return false;
                        const value = this.getNestedValue(row, header.key);
                        return String(value).toLowerCase().includes(query);
                    });
                });
            }
            
            this.currentPage = 1;
            this.updatePagination();
        },
        
        // Ordenamiento
        sort(key) {
            if (this.sortKey === key) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortKey = key;
                this.sortDirection = 'asc';
            }
            
            this.filteredData.sort((a, b) => {
                const aVal = this.getNestedValue(a, key);
                const bVal = this.getNestedValue(b, key);
                
                let comparison = 0;
                if (aVal > bVal) comparison = 1;
                if (aVal < bVal) comparison = -1;
                
                return this.sortDirection === 'asc' ? comparison : -comparison;
            });
            
            this.updatePagination();
        },
        
        // Paginación
        updatePagination() {
            const start = (this.currentPage - 1) * this.perPage;
            const end = start + this.perPage;
            this.paginatedData = this.filteredData.slice(start, end);
        },
        
        get totalPages() {
            return Math.ceil(this.filteredData.length / this.perPage) || 1;
        },
        
        get visibleColumnsCount() {
            return this.headers.filter(h => h.visible).length;
        },
        
        showPageButton(page) {
            // Mostrar primera, última, actual y 2 páginas alrededor
            if (page === 1 || page === this.totalPages) return true;
            if (Math.abs(page - this.currentPage) <= 2) return true;
            return false;
        },
        
        // Utilidades
        getNestedValue(obj, path) {
            return path.split('.').reduce((acc, part) => acc?.[part], obj) ?? '';
        },
        
        formatCell(value, header) {
            if (header.format) {
                return header.format(value);
            }
            return value ?? 'N/A';
        }
    }
}
</script>
