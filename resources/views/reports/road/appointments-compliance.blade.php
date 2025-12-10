@extends('layouts.app')

@section('title', 'R5 - Cumplimiento de Citas')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">
            R5 - Cumplimiento de Citas
        </h2>
        <p class="text-gray-600 mb-4">
            Análisis del cumplimiento de citas: A tiempo (±15 min), Tarde (>15 min), No Show
            @if($isTransportista)
                <span class="text-sm text-blue-600">(Mostrando solo datos de su empresa)</span>
            @endif
        </p>

        <!-- Filtros -->
        <x-filter-panel>
            <form method="GET" action="{{ route('reports.r5') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Fecha Desde -->
                <div>
                    <label for="fecha_desde" class="block text-sm font-medium text-gray-700 mb-1">
                        Fecha Desde
                    </label>
                    <input 
                        type="datetime-local" 
                        id="fecha_desde" 
                        name="fecha_desde"
                        value="{{ $filters['fecha_desde'] ?? '' }}"
                        class="input-field"
                    >
                </div>

                <!-- Fecha Hasta -->
                <div>
                    <label for="fecha_hasta" class="block text-sm font-medium text-gray-700 mb-1">
                        Fecha Hasta
                    </label>
                    <input 
                        type="datetime-local" 
                        id="fecha_hasta" 
                        name="fecha_hasta"
                        value="{{ $filters['fecha_hasta'] ?? '' }}"
                        class="input-field"
                    >
                </div>

                <!-- Empresa (solo visible para no-TRANSPORTISTA) -->
                @if(!$isTransportista && $companies->count() > 0)
                    <div>
                        <label for="company_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Empresa
                        </label>
                        <select 
                            id="company_id" 
                            name="company_id"
                            class="input-field"
                        >
                            <option value="">Todas</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ ($filters['company_id'] ?? '') == $company->id ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <!-- Botones -->
                <div class="md:col-span-3 flex gap-2">
                    <button type="submit" class="btn-primary">
                        Aplicar Filtros
                    </button>
                    <a href="{{ route('reports.r5') }}" class="btn-secondary">
                        Limpiar
                    </a>
                    @can('REPORT_EXPORT')
                        <button type="button" @click="exportReport('csv')" class="btn-secondary">
                            Exportar CSV
                        </button>
                        <button type="button" @click="exportReport('xlsx')" class="btn-secondary">
                            Exportar XLSX
                        </button>
                        <button type="button" @click="exportReport('pdf')" class="btn-secondary">
                            Exportar PDF
                        </button>
                    @endcan
                </div>
            </form>
        </x-filter-panel>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="card">
            <h3 class="text-sm font-medium text-gray-600 mb-2">No Show</h3>
            <p class="text-3xl font-bold text-red-600">
                {{ number_format($kpis['pct_no_show'], 2) }}%
            </p>
            <p class="text-xs text-gray-500 mt-1">Citas sin presentación</p>
        </div>

        <div class="card">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Tarde</h3>
            <p class="text-3xl font-bold text-orange-600">
                {{ number_format($kpis['pct_tarde'], 2) }}%
            </p>
            <p class="text-xs text-gray-500 mt-1">Llegadas con >15 min retraso</p>
        </div>

        <div class="card">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Desvío Medio</h3>
            <p class="text-3xl font-bold text-blue-600">
                {{ number_format($kpis['desvio_medio_min'], 2) }} min
            </p>
            <p class="text-xs text-gray-500 mt-1">Diferencia promedio</p>
        </div>

        <div class="card">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Total Citas</h3>
            <p class="text-3xl font-bold text-sgcmi-blue-900">
                {{ $kpis['total_citas'] }}
            </p>
            <p class="text-xs text-gray-500 mt-1">Citas analizadas</p>
        </div>
    </div>

    <!-- Ranking de Empresas (solo visible para no-TRANSPORTISTA) -->
    @if(!$isTransportista && $ranking !== null && $ranking->count() > 0)
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-xl font-semibold mb-4">Ranking de Empresas por Cumplimiento</h3>
            
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="table-header">
                        <tr>
                            <th class="px-4 py-2 text-left">Posición</th>
                            <th class="px-4 py-2 text-left">Empresa</th>
                            <th class="px-4 py-2 text-left">Total Citas</th>
                            <th class="px-4 py-2 text-left">A Tiempo</th>
                            <th class="px-4 py-2 text-left">No Show</th>
                            <th class="px-4 py-2 text-left">% Cumplimiento</th>
                            <th class="px-4 py-2 text-left">% No Show</th>
                            <th class="px-4 py-2 text-left">Calificación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ranking as $index => $empresa)
                            <tr class="table-row">
                                <td class="px-4 py-2 font-bold">
                                    @if($index === 0)
                                        🥇
                                    @elseif($index === 1)
                                        🥈
                                    @elseif($index === 2)
                                        🥉
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </td>
                                <td class="px-4 py-2 font-medium">{{ $empresa['company_name'] }}</td>
                                <td class="px-4 py-2">{{ $empresa['total_citas'] }}</td>
                                <td class="px-4 py-2 text-green-600 font-bold">{{ $empresa['a_tiempo'] }}</td>
                                <td class="px-4 py-2 text-red-600 font-bold">{{ $empresa['no_show'] }}</td>
                                <td class="px-4 py-2 font-bold text-sgcmi-blue-900">
                                    {{ number_format($empresa['pct_cumplimiento'], 2) }}%
                                </td>
                                <td class="px-4 py-2 font-bold text-red-600">
                                    {{ number_format($empresa['pct_no_show'], 2) }}%
                                </td>
                                <td class="px-4 py-2">
                                    @if($empresa['pct_cumplimiento'] >= 80)
                                        <span class="badge-success">Excelente</span>
                                    @elseif($empresa['pct_cumplimiento'] >= 60)
                                        <span class="badge-warning">Bueno</span>
                                    @else
                                        <span class="badge-danger">Mejorable</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Detalle de Citas -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-xl font-semibold mb-4">Detalle de Citas y Clasificación</h3>
        
        @if($data->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="table-header">
                        <tr>
                            <th class="px-4 py-2 text-left">ID</th>
                            <th class="px-4 py-2 text-left">Empresa</th>
                            <th class="px-4 py-2 text-left">Camión</th>
                            <th class="px-4 py-2 text-left">Hora Programada</th>
                            <th class="px-4 py-2 text-left">Hora Llegada</th>
                            <th class="px-4 py-2 text-left">Desvío (min)</th>
                            <th class="px-4 py-2 text-left">Clasificación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $cita)
                            <tr class="table-row">
                                <td class="px-4 py-2 font-medium">{{ $cita->id }}</td>
                                <td class="px-4 py-2">{{ $cita->company->name ?? 'N/A' }}</td>
                                <td class="px-4 py-2">{{ $cita->truck->placa ?? 'N/A' }}</td>
                                <td class="px-4 py-2">{{ $cita->hora_programada->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-2">{{ $cita->hora_llegada ? $cita->hora_llegada->format('Y-m-d H:i') : 'N/A' }}</td>
                                <td class="px-4 py-2 font-bold {{ $cita->desvio_min !== null && abs($cita->desvio_min) > 15 ? 'text-red-600' : 'text-green-600' }}">
                                    {{ $cita->desvio_min !== null ? number_format($cita->desvio_min, 2) : 'N/A' }}
                                </td>
                                <td class="px-4 py-2">
                                    @if($cita->clasificacion === 'NO_SHOW')
                                        <span class="badge-danger">No Show</span>
                                    @elseif($cita->clasificacion === 'TARDE')
                                        <span class="badge-warning">Tarde</span>
                                    @elseif($cita->clasificacion === 'A_TIEMPO')
                                        <span class="badge-success">A Tiempo</span>
                                    @else
                                        <span class="badge-info">Sin clasificar</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                No hay datos disponibles para los filtros seleccionados
            </div>
        @endif
    </div>

    <!-- Ayuda -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 class="font-semibold text-blue-900 mb-2">Ayuda</h3>
        <ul class="text-sm text-blue-800 space-y-1">
            <li><strong>A Tiempo:</strong> Llegada dentro de ±15 minutos de la hora programada</li>
            <li><strong>Tarde:</strong> Llegada con más de 15 minutos de retraso</li>
            <li><strong>No Show:</strong> Cita sin registro de llegada</li>
            <li><strong>Desvío Medio:</strong> Diferencia promedio en minutos entre hora programada y hora de llegada</li>
            @if($isTransportista)
                <li><strong>Scoping:</strong> Como TRANSPORTISTA, solo puede ver los datos de su propia empresa</li>
            @else
                <li><strong>Ranking:</strong> Empresas ordenadas por porcentaje de cumplimiento (citas a tiempo)</li>
            @endif
        </ul>
    </div>
</div>
@endsection
