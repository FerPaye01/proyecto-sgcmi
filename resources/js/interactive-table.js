/**
 * Sistema de Tablas Interactivas con Alpine.js
 * Proporciona búsqueda, ordenamiento, paginación y toggle de columnas
 */

window.interactiveTable = function(config) {
    return {
        // Configuración
        originalData: config.data || [],
        headers: (config.headers || []).map(h => ({
            key: h.key,
            label: h.label,
            visible: h.visible !== false,
            sortable: h.sortable !== false,
            format: h.format || null,
            class: h.class || ''
        })),
        
        // Estado
        searchQuery: '',
        sortKey: null,
        sortDirection: 'asc',
        currentPage: 1,
        perPage: config.perPage || 10,
        
        // Datos procesados
        filteredData: [],
        paginatedData: [],
        
        /**
         * Inicialización
         */
        init() {
            this.filteredData = [...this.originalData];
            this.updatePagination();
            
            // Watch para cambios en perPage
            this.$watch('perPage', () => {
                this.currentPage = 1;
                this.updatePagination();
            });
        },
        
        /**
         * Búsqueda en tiempo real
         */
        search() {
            const query = this.searchQuery.toLowerCase().trim();
            
            if (!query) {
                this.filteredData = [...this.originalData];
            } else {
                this.filteredData = this.originalData.filter(row => {
                    return this.headers.some(header => {
                        if (!header.visible) return false;
                        const value = this.getNestedValue(row, header.key);
                        const strValue = String(value).toLowerCase();
                        return strValue.includes(query);
                    });
                });
            }
            
            this.currentPage = 1;
            this.updatePagination();
        },
        
        /**
         * Ordenamiento por columna
         */
        sort(key) {
            if (this.sortKey === key) {
                // Toggle dirección
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                // Nueva columna
                this.sortKey = key;
                this.sortDirection = 'asc';
            }
            
            this.filteredData.sort((a, b) => {
                let aVal = this.getNestedValue(a, key);
                let bVal = this.getNestedValue(b, key);
                
                // Manejo de valores nulos
                if (aVal === null || aVal === undefined || aVal === 'N/A') return 1;
                if (bVal === null || bVal === undefined || bVal === 'N/A') return -1;
                
                // Detección de tipo numérico
                const aNum = parseFloat(aVal);
                const bNum = parseFloat(bVal);
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    aVal = aNum;
                    bVal = bNum;
                }
                
                // Comparación
                let comparison = 0;
                if (aVal > bVal) comparison = 1;
                if (aVal < bVal) comparison = -1;
                
                return this.sortDirection === 'asc' ? comparison : -comparison;
            });
            
            this.updatePagination();
        },
        
        /**
         * Actualizar datos paginados
         */
        updatePagination() {
            const start = (this.currentPage - 1) * this.perPage;
            const end = start + this.perPage;
            this.paginatedData = this.filteredData.slice(start, end);
        },
        
        /**
         * Navegar a página específica
         */
        goToPage(page) {
            if (page >= 1 && page <= this.totalPages) {
                this.currentPage = page;
                this.updatePagination();
            }
        },
        
        /**
         * Exportar datos visibles a CSV
         */
        exportToCSV() {
            const visibleHeaders = this.headers.filter(h => h.visible);
            
            // Encabezados
            let csv = visibleHeaders.map(h => this.escapeCSV(h.label)).join(',') + '\n';
            
            // Datos
            this.filteredData.forEach(row => {
                const values = visibleHeaders.map(header => {
                    const value = this.getNestedValue(row, header.key);
                    return this.escapeCSV(String(value ?? ''));
                });
                csv += values.join(',') + '\n';
            });
            
            // Descargar
            this.downloadFile(csv, 'export.csv', 'text/csv');
        },
        
        /**
         * Resetear filtros y ordenamiento
         */
        reset() {
            this.searchQuery = '';
            this.sortKey = null;
            this.sortDirection = 'asc';
            this.currentPage = 1;
            this.filteredData = [...this.originalData];
            this.updatePagination();
        },
        
        /**
         * Mostrar/ocultar todas las columnas
         */
        toggleAllColumns(visible) {
            this.headers.forEach(h => h.visible = visible);
        },
        
        // ===== COMPUTED PROPERTIES =====
        
        get totalPages() {
            return Math.ceil(this.filteredData.length / this.perPage) || 1;
        },
        
        get visibleColumnsCount() {
            return this.headers.filter(h => h.visible).length;
        },
        
        get startRecord() {
            return ((this.currentPage - 1) * this.perPage) + 1;
        },
        
        get endRecord() {
            return Math.min(this.currentPage * this.perPage, this.filteredData.length);
        },
        
        get hasResults() {
            return this.filteredData.length > 0;
        },
        
        get pageNumbers() {
            const pages = [];
            const total = this.totalPages;
            const current = this.currentPage;
            
            // Siempre mostrar primera página
            pages.push(1);
            
            // Páginas alrededor de la actual
            for (let i = Math.max(2, current - 2); i <= Math.min(total - 1, current + 2); i++) {
                pages.push(i);
            }
            
            // Siempre mostrar última página
            if (total > 1) {
                pages.push(total);
            }
            
            // Eliminar duplicados y ordenar
            return [...new Set(pages)].sort((a, b) => a - b);
        },
        
        // ===== UTILIDADES =====
        
        /**
         * Obtener valor anidado de objeto
         */
        getNestedValue(obj, path) {
            return path.split('.').reduce((acc, part) => {
                return acc?.[part];
            }, obj) ?? null;
        },
        
        /**
         * Formatear celda según configuración
         */
        formatCell(value, header) {
            if (header.format) {
                if (typeof header.format === 'function') {
                    return header.format(value);
                } else if (typeof header.format === 'string') {
                    // Evaluar función como string usando Function constructor (más seguro que eval)
                    try {
                        const fn = new Function('val', `return (${header.format})(val);`);
                        return fn(value);
                    } catch (e) {
                        console.error('Error evaluating format function:', e);
                        return value;
                    }
                }
            }
            return value ?? 'N/A';
        },
        
        /**
         * Determinar si mostrar botón de página
         */
        showPageButton(page) {
            return this.pageNumbers.includes(page);
        },
        
        /**
         * Escapar valores para CSV
         */
        escapeCSV(str) {
            if (str.includes(',') || str.includes('"') || str.includes('\n')) {
                return '"' + str.replace(/"/g, '""') + '"';
            }
            return str;
        },
        
        /**
         * Descargar archivo
         */
        downloadFile(content, filename, mimeType) {
            const blob = new Blob([content], { type: mimeType });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }
    };
};

/**
 * Utilidad para crear configuración de tabla desde elemento DOM
 */
window.createTableConfig = function(element) {
    const data = JSON.parse(element.dataset.tableData || '[]');
    const headers = JSON.parse(element.dataset.tableHeaders || '[]');
    const perPage = parseInt(element.dataset.perPage || '10');
    
    return { data, headers, perPage };
};
