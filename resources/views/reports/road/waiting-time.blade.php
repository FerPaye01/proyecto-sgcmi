@extends('layouts.app')

@section('title', 'R4 - Tiempo de Espera de Camiones')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">
            R4 - Tiempo de Espera de Camiones
        </h2>
        <p class="text-gray-600 mb-4">
            Análisis del tiempo de espera desde la llegada hasta el primer evento de gate
            @if($isTransportista)
                <span class="text-sm text-blue-600">(Mostrando solo datos de su empresa)</span>
            @endif
        </p>

        <!-- Filtros -->
        <x-filter-panel>
            <form method="GET" action="{{ route('reports.r4') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                    <a href="{{ route('reports.r4') }}" class="btn-secondary">
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
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="card">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Espera Promedio</h3>
            <p class="text-3xl font-bold text-sgcmi-blue-900">
                {{ number_format($kpis['espera_promedio_h'], 2) }} h
            </p>
            <p class="text-xs text-gray-500 mt-1">Tiempo promedio de espera</p>
        </div>

        <div class="card">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Espera > 6 horas</h3>
            <p class="text-3xl font-bold {{ $kpis['pct_gt_6h'] > 20 ? 'text-red-600' : 'text-green-600' }}">
                {{ number_format($kpis['pct_gt_6h'], 2) }}%
            </p>
            <p class="text-xs text-gray-500 mt-1">Porcentaje con espera excesiva</p>
        </div>

        <div class="card">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Citas Atendidas</h3>
            <p class="text-3xl font-bold text-blue-600">
                {{ $kpis['citas_atendidas'] }}
            </p>
            <p class="text-xs text-gray-500 mt-1">Total de citas procesadas</p>
        </div>
    </div>

    <!-- Detalle de Citas -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-xl font-semibold mb-4">Detalle de Citas y Tiempos de Espera</h3>
        
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
                            <th class="px-4 py-2 text-left">Espera (horas)</th>
                            <th class="px-4 py-2 text-left">Estado</th>
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
                                <td class="px-4 py-2 font-bold {{ $cita->espera_horas !== null && $cita->espera_horas > 6 ? 'text-red-600' : 'text-green-600' }}">
                                    {{ $cita->espera_horas !== null ? number_format($cita->espera_horas, 2) : 'N/A' }}
                                </td>
                                <td class="px-4 py-2">
                                    @if($cita->espera_horas !== null && $cita->espera_horas > 6)
                                        <span class="badge-danger">Espera Excesiva</span>
                                    @elseif($cita->espera_horas !== null && $cita->espera_horas > 2)
                                        <span class="badge-warning">Espera Alta</span>
                                    @elseif($cita->espera_horas !== null)
                                        <span class="badge-success">Normal</span>
                                    @else
                                        <span class="badge-info">Sin datos</span>
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
            <li><strong>Espera Promedio:</strong> Tiempo promedio desde la hora de llegada hasta el primer evento de gate</li>
            <li><strong>Espera > 6 horas:</strong> Porcentaje de citas con tiempo de espera superior a 6 horas</li>
            <li><strong>Citas Atendidas:</strong> Total de citas que tienen registro de llegada y eventos de gate</li>
            @if($isTransportista)
                <li><strong>Scoping:</strong> Como TRANSPORTISTA, solo puede ver los datos de su propia empresa</li>
            @endif
        </ul>
    </div>
</div>
@endsection
