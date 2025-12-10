import './bootstrap';
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import './interactive-table';

// Make Alpine and Chart.js available globally
window.Alpine = Alpine;
window.Chart = Chart;

// Alpine.js components for SGCMI

// Report filters component
Alpine.data('reportFilters', () => ({
    filters: {
        fecha_desde: '',
        fecha_hasta: '',
        berth_id: '',
        vessel_id: '',
        company_id: '',
        gate_id: '',
        estado: ''
    },
    
    init() {
        // Load filters from URL params
        const params = new URLSearchParams(window.location.search);
        for (const [key, value] of params) {
            if (this.filters.hasOwnProperty(key)) {
                this.filters[key] = value;
            }
        }
    },
    
    applyFilters() {
        const params = new URLSearchParams();
        for (const [key, value] of Object.entries(this.filters)) {
            if (value) {
                params.append(key, value);
            }
        }
        window.location.href = `?${params.toString()}`;
    },
    
    clearFilters() {
        this.filters = {
            fecha_desde: '',
            fecha_hasta: '',
            berth_id: '',
            vessel_id: '',
            company_id: '',
            gate_id: '',
            estado: ''
        };
        window.location.href = window.location.pathname;
    },
    
    exportReport(format) {
        const params = new URLSearchParams(this.filters);
        params.append('format', format);
        window.location.href = `/export/${window.location.pathname.split('/').pop()}?${params.toString()}`;
    }
}));

// Vessel Call Date validation component
Alpine.data('vesselCallForm', (initialData = {}) => ({
    eta: initialData.eta || '',
    etb: initialData.etb || '',
    ata: initialData.ata || '',
    atb: initialData.atb || '',
    atd: initialData.atd || '',
    validationError: '',
    fieldErrors: {},
    
    init() {
        // Watch for changes and validate in real-time
        this.$watch('eta', () => this.validateField('eta'));
        this.$watch('etb', () => this.validateField('etb'));
        this.$watch('ata', () => this.validateField('ata'));
        this.$watch('atb', () => this.validateField('atb'));
        this.$watch('atd', () => this.validateField('atd'));
    },
    
    validateField(field) {
        // Clear previous errors for this field
        delete this.fieldErrors[field];
        this.validationError = '';
        
        // Validate ETB >= ETA
        if (field === 'etb' && this.eta && this.etb) {
            const etaDate = new Date(this.eta);
            const etbDate = new Date(this.etb);
            
            if (etbDate < etaDate) {
                this.fieldErrors.etb = 'ETB debe ser mayor o igual a ETA';
            }
        }
        
        // Validate ATB >= ATA
        if ((field === 'atb' || field === 'ata') && this.ata && this.atb) {
            const ataDate = new Date(this.ata);
            const atbDate = new Date(this.atb);
            
            if (atbDate < ataDate) {
                this.fieldErrors.atb = 'ATB debe ser mayor o igual a ATA';
            }
        }
        
        // Validate ATD >= ATB
        if ((field === 'atd' || field === 'atb') && this.atb && this.atd) {
            const atbDate = new Date(this.atb);
            const atdDate = new Date(this.atd);
            
            if (atdDate < atbDate) {
                this.fieldErrors.atd = 'ATD debe ser mayor o igual a ATB';
            }
        }
    },
    
    validateDates(event) {
        this.validationError = '';
        this.fieldErrors = {};
        
        // Validate ETB >= ETA
        if (this.eta && this.etb) {
            const etaDate = new Date(this.eta);
            const etbDate = new Date(this.etb);
            
            if (etbDate < etaDate) {
                this.validationError = 'ETB debe ser mayor o igual a ETA';
                this.fieldErrors.etb = 'ETB debe ser mayor o igual a ETA';
                event.preventDefault();
                return false;
            }
        }
        
        // Validate ATB >= ATA
        if (this.ata && this.atb) {
            const ataDate = new Date(this.ata);
            const atbDate = new Date(this.atb);
            
            if (atbDate < ataDate) {
                this.validationError = 'ATB debe ser mayor o igual a ATA';
                this.fieldErrors.atb = 'ATB debe ser mayor o igual a ATA';
                event.preventDefault();
                return false;
            }
        }
        
        // Validate ATD >= ATB
        if (this.atb && this.atd) {
            const atbDate = new Date(this.atb);
            const atdDate = new Date(this.atd);
            
            if (atdDate < atbDate) {
                this.validationError = 'ATD debe ser mayor o igual a ATB';
                this.fieldErrors.atd = 'ATD debe ser mayor o igual a ATB';
                event.preventDefault();
                return false;
            }
        }
        
        return true;
    },
    
    hasError(field) {
        return this.fieldErrors.hasOwnProperty(field);
    },
    
    getError(field) {
        return this.fieldErrors[field] || '';
    },
    
    getFieldClass(field, baseClass = '') {
        const errorClass = this.hasError(field) ? 'border-red-500 focus:ring-red-500' : '';
        return `${baseClass} ${errorClass}`.trim();
    }
}));

// Alias for backward compatibility with test page
Alpine.data('dateValidator', (initialData = {}) => {
    return Alpine.raw(Alpine.data('vesselCallForm')(initialData));
});

// KPI panel component with auto-refresh
Alpine.data('kpiPanel', (refreshInterval = 300000) => ({
    loading: false,
    lastUpdate: null,
    
    init() {
        this.lastUpdate = new Date();
        // Auto-refresh every 5 minutes (300000ms)
        setInterval(() => {
            this.refresh();
        }, refreshInterval);
    },
    
    async refresh() {
        this.loading = true;
        try {
            // Reload the page to get fresh data
            window.location.reload();
        } catch (error) {
            console.error('Error refreshing KPIs:', error);
        } finally {
            this.loading = false;
            this.lastUpdate = new Date();
        }
    },
    
    getLastUpdateText() {
        if (!this.lastUpdate) return '';
        return `Última actualización: ${this.lastUpdate.toLocaleTimeString('es-PE')}`;
    }
}));

// Modal component
Alpine.data('modal', (isOpen = false) => ({
    open: isOpen,
    
    show() {
        this.open = true;
    },
    
    hide() {
        this.open = false;
    },
    
    toggle() {
        this.open = !this.open;
    }
}));

// Confirmation dialog component
Alpine.data('confirmDialog', () => ({
    show: false,
    message: '',
    onConfirm: null,
    
    confirm(message, callback) {
        this.message = message;
        this.onConfirm = callback;
        this.show = true;
    },
    
    handleConfirm() {
        if (this.onConfirm) {
            this.onConfirm();
        }
        this.show = false;
    },
    
    handleCancel() {
        this.show = false;
        this.onConfirm = null;
    }
}));

// Appointment capacity validator
Alpine.data('appointmentValidator', (maxCapacity = 10) => ({
    hora_programada: '',
    currentCount: 0,
    maxCapacity: maxCapacity,
    
    async checkCapacity() {
        if (!this.hora_programada) return true;
        
        // In a real implementation, this would make an AJAX call
        // For now, we'll just validate client-side
        return this.currentCount < this.maxCapacity;
    },
    
    isOverCapacity() {
        return this.currentCount >= this.maxCapacity;
    },
    
    getCapacityText() {
        return `${this.currentCount} / ${this.maxCapacity} citas programadas`;
    }
}));

// Start Alpine
Alpine.start();
