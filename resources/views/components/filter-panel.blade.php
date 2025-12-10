@props(['action' => '#', 'showBerth' => false, 'showVessel' => false, 'showCompany' => false, 'showGate' => false, 'showEstado' => false])

<div x-data="reportFilters()" class="card mb-6">
    <h3 class="text-lg font-semibold mb-4">Filtros</h3>
    
    <form @submit.prevent="applyFilters()" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Fecha Desde -->
        <div>
            <label for="fecha_desde" class="block text-sm font-medium text-gray-700 mb-1">
                Fecha Desde
            </label>
            <input 
                type="date" 
                id="fecha_desde" 
                x-model="filters.fecha_desde"
                class="input-field"
            >
        </div>
        
        <!-- Fecha Hasta -->
        <div>
            <label for="fecha_hasta" class="block text-sm font-medium text-gray-700 mb-1">
                Fecha Hasta
            </label>
            <input 
                type="date" 
                id="fecha_hasta" 
                x-model="filters.fecha_hasta"
                class="input-field"
            >
        </div>
        
        <!-- Muelle (conditional) -->
        @if($showBerth)
        <div>
            <label for="berth_id" class="block text-sm font-medium text-gray-700 mb-1">
                Muelle
            </label>
            <select 
                id="berth_id" 
                x-model="filters.berth_id"
                class="input-field"
            >
                <option value="">Todos</option>
                {{ $slot }}
            </select>
        </div>
        @endif
        
        <!-- Nave (conditional) -->
        @if($showVessel)
        <div>
            <label for="vessel_id" class="block text-sm font-medium text-gray-700 mb-1">
                Nave
            </label>
            <select 
                id="vessel_id" 
                x-model="filters.vessel_id"
                class="input-field"
            >
                <option value="">Todas</option>
                {{ $slot }}
            </select>
        </div>
        @endif
        
        <!-- Empresa (conditional) -->
        @if($showCompany)
        <div>
            <label for="company_id" class="block text-sm font-medium text-gray-700 mb-1">
                Empresa
            </label>
            <select 
                id="company_id" 
                x-model="filters.company_id"
                class="input-field"
            >
                <option value="">Todas</option>
                {{ $slot }}
            </select>
        </div>
        @endif
        
        <!-- Gate (conditional) -->
        @if($showGate)
        <div>
            <label for="gate_id" class="block text-sm font-medium text-gray-700 mb-1">
                Gate
            </label>
            <select 
                id="gate_id" 
                x-model="filters.gate_id"
                class="input-field"
            >
                <option value="">Todos</option>
                {{ $slot }}
            </select>
        </div>
        @endif
        
        <!-- Estado (conditional) -->
        @if($showEstado)
        <div>
            <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">
                Estado
            </label>
            <select 
                id="estado" 
                x-model="filters.estado"
                class="input-field"
            >
                <option value="">Todos</option>
                {{ $slot }}
            </select>
        </div>
        @endif
        
        <!-- Buttons -->
        <div class="flex items-end space-x-2 md:col-span-2 lg:col-span-3">
            <button type="submit" class="btn-primary">
                Aplicar Filtros
            </button>
            <button type="button" @click="clearFilters()" class="btn-secondary">
                Limpiar
            </button>
        </div>
    </form>
</div>
