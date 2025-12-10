@extends('layouts.app')

@section('title', 'Panel de KPIs Ejecutivo')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="kpiPanel()" x-init="initPolling()">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Panel de KPIs Ejecutivo</h1>
        <p class="text-gray-600">Visualización consolidada de indicadores clave del sistema</p>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <form method="GET" action="{{ route('reports.r10') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="fecha_desde" class="block text-sm font-medium text-gray-700 mb-1">Fecha Desde</label>
                <input type="date" id="fecha_desde" name="fecha_desde" value="{{ $filters['fecha_desde'] ?? '' }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500">
            </div>

            <div>
                <label for="fecha_hasta" class="block text-sm font-medium text-gray-700 mb-1">Fecha Hasta</label>
                <input type="date" id="fecha_hasta" name="fecha_hasta" value="{{ $filters['fecha_hasta'] ?? '' }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                    Aplicar Filtros
                </button>
                <a href="{{ route('reports.r10') }}" class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition text-center">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    <!-- Periodos -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
            <h3 class="font-semibold text-blue-900 mb-2">Período Actual</h3>
            <p class="text-sm text-blue-700">
                {{ \Carbon\Carbon::parse($periodo_actual['fecha_desde'])->format('d/m/Y H:i') }} 
                a 
                {{ \Carbon\Carbon::parse($periodo_actual['fecha_hasta'])->format('d/m/Y H:i') }}
            </p>
        </div>
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
            <h3 class="font-semibold text-gray-900 mb-2">Período Anterior</h3>
            <p class="text-sm text-gray-700">
                {{ \Carbon\Carbon::parse($periodo_anterior['fecha_desde'])->format('d/m/Y H:i') }} 
                a 
                {{ \Carbon\Carbon::parse($periodo_anterior['fecha_hasta'])->format('d/m/Y H:i') }}
            </p>
        </div>
    </div>

    <!-- KPIs Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- KPI: Turnaround -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500" data-kpi="turnaround">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Turnaround Promedio</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" data-kpi-value>
                        {{ $kpis['turnaround']['valor_actual'] }}h
                    </p>
                </div>
                <div class="text-2xl {{ $kpis['turnaround']['tendencia_positiva'] ? 'text-green-500' : 'text-red-500' }}" data-kpi-trend>
                    {{ $kpis['turnaround']['tendencia'] }}
                </div>
            </div>

            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Meta:</span>
                    <span class="font-semibold">{{ $kpis['turnaround']['meta'] }}h</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Período Anterior:</span>
                    <span class="font-semibold" data-kpi-prev>{{ $kpis['turnaround']['valor_anterior'] }}h</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Cambio:</span>
                    <span class="font-semibold {{ $kpis['turnaround']['diferencia'] < 0 ? 'text-green-600' : 'text-red-600' }}" data-kpi-change>
                        {{ $kpis['turnaround']['diferencia'] > 0 ? '+' : '' }}{{ $kpis['turnaround']['diferencia'] }}h
                        ({{ $kpis['turnaround']['pct_cambio'] > 0 ? '+' : '' }}{{ $kpis['turnaround']['pct_cambio'] }}%)
                    </span>
                </div>
                <div class="pt-2 border-t border-gray-200">
                    <div class="flex items-center gap-2">
                        <div class="flex-1 bg-gray-200 rounded-full h-2">
                            <div class="bg-purple-500 h-2 rounded-full" data-kpi-progress
                                style="width: {{ min(100, ($kpis['turnaround']['valor_actual'] / $kpis['turnaround']['meta']) * 100) }}%"></div>
                        </div>
                        <span class="text-xs font-semibold {{ $kpis['turnaround']['cumple_meta'] ? 'text-green-600' : 'text-red-600' }}" data-kpi-status>
                            {{ $kpis['turnaround']['cumple_meta'] ? '✓ OK' : '✗ Fuera' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI: Espera de Camión -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500" data-kpi="espera_camion">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Espera de Camión</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" data-kpi-value>
                        {{ $kpis['espera_camion']['valor_actual'] }}h
                    </p>
                </div>
                <div class="text-2xl {{ $kpis['espera_camion']['tendencia_positiva'] ? 'text-green-500' : 'text-red-500' }}" data-kpi-trend>
                    {{ $kpis['espera_camion']['tendencia'] }}
                </div>
            </div>

            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Meta:</span>
                    <span class="font-semibold">{{ $kpis['espera_camion']['meta'] }}h</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Período Anterior:</span>
                    <span class="font-semibold" data-kpi-prev>{{ $kpis['espera_camion']['valor_anterior'] }}h</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Cambio:</span>
                    <span class="font-semibold {{ $kpis['espera_camion']['diferencia'] < 0 ? 'text-green-600' : 'text-red-600' }}" data-kpi-change>
                        {{ $kpis['espera_camion']['diferencia'] > 0 ? '+' : '' }}{{ $kpis['espera_camion']['diferencia'] }}h
                        ({{ $kpis['espera_camion']['pct_cambio'] > 0 ? '+' : '' }}{{ $kpis['espera_camion']['pct_cambio'] }}%)
                    </span>
                </div>
                <div class="pt-2 border-t border-gray-200">
                    <div class="flex items-center gap-2">
                        <div class="flex-1 bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full" data-kpi-progress
                                style="width: {{ min(100, ($kpis['espera_camion']['valor_actual'] / $kpis['espera_camion']['meta']) * 100) }}%"></div>
                        </div>
                        <span class="text-xs font-semibold {{ $kpis['espera_camion']['cumple_meta'] ? 'text-green-600' : 'text-red-600' }}" data-kpi-status>
                            {{ $kpis['espera_camion']['cumple_meta'] ? '✓ OK' : '✗ Fuera' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI: Cumplimiento de Citas -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500" data-kpi="cumpl_citas">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Cumplimiento de Citas</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" data-kpi-value>
                        {{ $kpis['cumpl_citas']['valor_actual'] }}%
                    </p>
                </div>
                <div class="text-2xl {{ $kpis['cumpl_citas']['tendencia_positiva'] ? 'text-green-500' : 'text-red-500' }}" data-kpi-trend>
                    {{ $kpis['cumpl_citas']['tendencia'] }}
                </div>
            </div>

            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Meta:</span>
                    <span class="font-semibold">{{ $kpis['cumpl_citas']['meta'] }}%</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Período Anterior:</span>
                    <span class="font-semibold" data-kpi-prev>{{ $kpis['cumpl_citas']['valor_anterior'] }}%</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Cambio:</span>
                    <span class="font-semibold {{ $kpis['cumpl_citas']['diferencia'] > 0 ? 'text-green-600' : 'text-red-600' }}" data-kpi-change>
                        {{ $kpis['cumpl_citas']['diferencia'] > 0 ? '+' : '' }}{{ $kpis['cumpl_citas']['diferencia'] }}%
                        ({{ $kpis['cumpl_citas']['pct_cambio'] > 0 ? '+' : '' }}{{ $kpis['cumpl_citas']['pct_cambio'] }}%)
                    </span>
                </div>
                <div class="pt-2 border-t border-gray-200">
                    <div class="flex items-center gap-2">
                        <div class="flex-1 bg-gray-200 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full" data-kpi-progress
                                style="width: {{ min(100, $kpis['cumpl_citas']['valor_actual']) }}%"></div>
                        </div>
                        <span class="text-xs font-semibold {{ $kpis['cumpl_citas']['cumple_meta'] ? 'text-green-600' : 'text-red-600' }}" data-kpi-status>
                            {{ $kpis['cumpl_citas']['cumple_meta'] ? '✓ OK' : '✗ Fuera' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI: Trámites OK -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-orange-500" data-kpi="tramites_ok">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Trámites Aprobados</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" data-kpi-value>
                        {{ $kpis['tramites_ok']['valor_actual'] }}%
                    </p>
                </div>
                <div class="text-2xl {{ $kpis['tramites_ok']['tendencia_positiva'] ? 'text-green-500' : 'text-red-500' }}" data-kpi-trend>
                    {{ $kpis['tramites_ok']['tendencia'] }}
                </div>
            </div>

            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Meta:</span>
                    <span class="font-semibold">{{ $kpis['tramites_ok']['meta'] }}%</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Período Anterior:</span>
                    <span class="font-semibold" data-kpi-prev>{{ $kpis['tramites_ok']['valor_anterior'] }}%</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Cambio:</span>
                    <span class="font-semibold {{ $kpis['tramites_ok']['diferencia'] > 0 ? 'text-green-600' : 'text-red-600' }}" data-kpi-change>
                        {{ $kpis['tramites_ok']['diferencia'] > 0 ? '+' : '' }}{{ $kpis['tramites_ok']['diferencia'] }}%
                        ({{ $kpis['tramites_ok']['pct_cambio'] > 0 ? '+' : '' }}{{ $kpis['tramites_ok']['pct_cambio'] }}%)
                    </span>
                </div>
                <div class="pt-2 border-t border-gray-200">
                    <div class="flex items-center gap-2">
                        <div class="flex-1 bg-gray-200 rounded-full h-2">
                            <div class="bg-orange-500 h-2 rounded-full" data-kpi-progress
                                style="width: {{ min(100, $kpis['tramites_ok']['valor_actual']) }}%"></div>
                        </div>
                        <span class="text-xs font-semibold {{ $kpis['tramites_ok']['cumple_meta'] ? 'text-green-600' : 'text-red-600' }}" data-kpi-status>
                            {{ $kpis['tramites_ok']['cumple_meta'] ? '✓ OK' : '✗ Fuera' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leyenda -->
    <div class="mt-8 bg-gray-50 rounded-lg p-4 border border-gray-200">
        <h3 class="font-semibold text-gray-900 mb-3">Leyenda de Indicadores</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-gray-700"><strong>Turnaround:</strong> Tiempo total desde arribo hasta salida de una nave (horas)</p>
            </div>
            <div>
                <p class="text-gray-700"><strong>Espera de Camión:</strong> Tiempo promedio de espera desde llegada hasta atención (horas)</p>
            </div>
            <div>
                <p class="text-gray-700"><strong>Cumplimiento de Citas:</strong> Porcentaje de citas atendidas a tiempo (±15 minutos)</p>
            </div>
            <div>
                <p class="text-gray-700"><strong>Trámites Aprobados:</strong> Porcentaje de trámites aduaneros aprobados</p>
            </div>
        </div>
    </div>

    <!-- Polling Status Indicator -->
    <div class="mt-8 flex items-center justify-center gap-2 text-sm text-gray-600">
        <div class="w-2 h-2 rounded-full" :class="isPolling ? 'bg-green-500 animate-pulse' : 'bg-gray-400'"></div>
        <span x-text="isPolling ? 'Actualizando automáticamente cada 5 minutos' : 'Actualización automática desactivada'"></span>
        <span x-text="'(Última actualización: ' + lastUpdate + ')'"></span>
    </div>
</div>

<script>
function kpiPanel() {
    return {
        isPolling: true,
        lastUpdate: new Date().toLocaleTimeString('es-PE'),
        pollingInterval: null,
        pollIntervalMs: 5 * 60 * 1000, // 5 minutes in milliseconds

        initPolling() {
            // Start polling immediately
            this.pollKpiData();

            // Set up interval for polling every 5 minutes
            this.pollingInterval = setInterval(() => {
                this.pollKpiData();
            }, this.pollIntervalMs);
        },

        async pollKpiData() {
            try {
                // Get current filter parameters from the form
                const params = new URLSearchParams();
                
                const fechaDesde = document.querySelector('input[name="fecha_desde"]');
                const fechaHasta = document.querySelector('input[name="fecha_hasta"]');
                
                if (fechaDesde && fechaDesde.value) {
                    params.append('fecha_desde', fechaDesde.value);
                }
                if (fechaHasta && fechaHasta.value) {
                    params.append('fecha_hasta', fechaHasta.value);
                }

                // Fetch updated KPI data from API
                const response = await fetch(`{{ route('reports.r10.api') }}?${params.toString()}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    console.error('Failed to fetch KPI data:', response.status);
                    return;
                }

                const data = await response.json();

                // Update KPI cards with new data
                this.updateKpiCards(data.kpis);

                // Update last update timestamp
                this.lastUpdate = new Date().toLocaleTimeString('es-PE');
            } catch (error) {
                console.error('Error polling KPI data:', error);
            }
        },

        updateKpiCards(kpis) {
            // Update Turnaround KPI
            this.updateKpiCard('turnaround', kpis.turnaround);

            // Update Espera de Camión KPI
            this.updateKpiCard('espera_camion', kpis.espera_camion);

            // Update Cumplimiento de Citas KPI
            this.updateKpiCard('cumpl_citas', kpis.cumpl_citas);

            // Update Trámites OK KPI
            this.updateKpiCard('tramites_ok', kpis.tramites_ok);
        },

        updateKpiCard(kpiName, kpiData) {
            // Find the card container for this KPI
            const cardSelector = `[data-kpi="${kpiName}"]`;
            const card = document.querySelector(cardSelector);

            if (!card) {
                console.warn(`KPI card not found for ${kpiName}`);
                return;
            }

            // Update main value
            const valueElement = card.querySelector('[data-kpi-value]');
            if (valueElement) {
                const unit = kpiName === 'cumpl_citas' || kpiName === 'tramites_ok' ? '%' : 'h';
                valueElement.textContent = kpiData.valor_actual + unit;
            }

            // Update trend indicator
            const trendElement = card.querySelector('[data-kpi-trend]');
            if (trendElement) {
                trendElement.textContent = kpiData.tendencia;
                trendElement.className = kpiData.tendencia_positiva 
                    ? 'text-2xl text-green-500' 
                    : 'text-2xl text-red-500';
            }

            // Update previous period value
            const prevElement = card.querySelector('[data-kpi-prev]');
            if (prevElement) {
                const unit = kpiName === 'cumpl_citas' || kpiName === 'tramites_ok' ? '%' : 'h';
                prevElement.textContent = kpiData.valor_anterior + unit;
            }

            // Update change value
            const changeElement = card.querySelector('[data-kpi-change]');
            if (changeElement) {
                const unit = kpiName === 'cumpl_citas' || kpiName === 'tramites_ok' ? '%' : 'h';
                const sign = kpiData.diferencia > 0 ? '+' : '';
                const isPositive = (kpiName === 'cumpl_citas' || kpiName === 'tramites_ok') 
                    ? kpiData.diferencia > 0 
                    : kpiData.diferencia < 0;
                
                changeElement.textContent = `${sign}${kpiData.diferencia}${unit} (${sign}${kpiData.pct_cambio}%)`;
                changeElement.className = isPositive 
                    ? 'font-semibold text-green-600' 
                    : 'font-semibold text-red-600';
            }

            // Update progress bar
            const progressBar = card.querySelector('[data-kpi-progress]');
            if (progressBar) {
                let percentage = 0;
                if (kpiName === 'turnaround') {
                    percentage = Math.min(100, (kpiData.valor_actual / kpiData.meta) * 100);
                } else if (kpiName === 'espera_camion') {
                    percentage = Math.min(100, (kpiData.valor_actual / kpiData.meta) * 100);
                } else {
                    percentage = Math.min(100, kpiData.valor_actual);
                }
                progressBar.style.width = percentage + '%';
            }

            // Update status badge
            const statusBadge = card.querySelector('[data-kpi-status]');
            if (statusBadge) {
                statusBadge.textContent = kpiData.cumple_meta ? '✓ OK' : '✗ Fuera';
                statusBadge.className = kpiData.cumple_meta 
                    ? 'text-xs font-semibold text-green-600' 
                    : 'text-xs font-semibold text-red-600';
            }
        },

        destroy() {
            if (this.pollingInterval) {
                clearInterval(this.pollingInterval);
            }
        }
    };
}
</script>
@endsection
