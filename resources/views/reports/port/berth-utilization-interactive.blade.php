@extends('layouts.app')

@section('title', 'R3 - Utilización de Muelles (Interactivo)')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">
            R3 - Utilización de Muelles
        </h2>
        <p class="text-gray-600 mb-4">
            Análisis de utilización horaria de muelles con tabla interactiva
        </p>

        <!-- Filtros existentes... -->
        <x-filter-panel :showBerth="true">
            <form method="GET" action="{{ route('reports.r3') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
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

                <!-- Muelle -->
                <div>
                    <label for="berth_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Muelle
                    </label>
                    <select 
                        id="berth_id" 
                        name="berth_id"
                        class="input-field"
                    >
                        <option value="">Todos</option>
                        @foreach($berths as $berth)
                            <option value="{{ $berth->id }}" {{ ($filters['berth_id'] ?? '') == $berth->id ? 'selected' : '' }}>
                                {{ $berth->name }} ({{ $berth->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Botones -->
                <div class="md:col-span-4 flex gap-2">
                    <button type="submit" class="btn-primary">
                        Aplicar Filtros
                    </button>
                    <a href="{{ route('reports.r3') }}" class="btn-secondary">
                        Limpiar
                    </a>
                </div>
            </form>
        </x-filter-panel>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="card">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Utilización Promedio</h3>
            <p class="text-3xl font-bold text-sgcmi-blue-900">
                {{ number_format($kpis['utilizacion_promedio'], 2) }}%
            </p>
        </div>

        <div class="card">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Conflictos de Ventana</h3>
            <p class="text-3xl font-bold {{ $kpis['conflictos_ventana'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                {{ $kpis['conflictos_ventana'] }}
            </p>
        </div>

        <div class="card">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Horas Ociosas</h3>
            <p class="text-3xl font-bold text-yellow-600">
                {{ number_format($kpis['horas_ociosas'], 2) }}h
            </p>
        </div>

        <div class="card">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Utilización Máxima</h3>
            <p class="text-3xl font-bold text-sgcmi-blue-900">
                {{ number_format($kpis['utilizacion_maxima'], 2) }}%
            </p>
        </div>
    </div>

    <!-- Tabla Interactiva de Llamadas -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-xl font-semibold mb-4">Detalle de Llamadas de Naves</h3>
        
        @php
            $tableData = $data->map(function($vesselCall) {
                $permanencia = null;
                if ($vesselCall->atb && $vesselCall->atd) {
                    $permanencia = number_format(($vesselCall->atd->timestamp - $vesselCall->atb->timestamp) / 3600, 2);
                }
                
                return [
                    'id' => $vesselCall->id,
                    'nave' => $vesselCall->vessel->name ?? 'N/A',
                    'viaje' => $vesselCall->viaje_id,
                    'muelle' => $vesselCall->berth->name ?? 'N/A',
                    'atb' => $vesselCall->atb?->format('Y-m-d H:i') ?? 'N/A',
                    'atd' => $vesselCall->atd?->format('Y-m-d H:i') ?? 'N/A',
                    'permanencia' => $permanencia,
                    'estado' => $vesselCall->estado_llamada,
                    'estado_raw' => $vesselCall->estado_llamada
                ];
            })->toArray();
            
            $tableHeaders = [
                ['key' => 'id', 'label' => 'ID', 'sortable' => true],
                ['key' => 'nave', 'label' => 'Nave', 'sortable' => true],
                ['key' => 'viaje', 'label' => 'Viaje', 'sortable' => true],
                ['key' => 'muelle', 'label' => 'Muelle', 'sortable' => true],
                ['key' => 'atb', 'label' => 'ATB', 'sortable' => true],
                ['key' => 'atd', 'label' => 'ATD', 'sortable' => true],
                [
                    'key' => 'permanencia', 
                    'label' => 'Permanencia (h)', 
                    'sortable' => true,
                    'format' => 'function(val) { return val ? val + "h" : "N/A"; }'
                ],
                [
                    'key' => 'estado', 
                    'label' => 'Estado', 
                    'sortable' => true,
                    'format' => 'function(val) {
                        const badges = {
                            "COMPLETADA": "<span class=\"badge-success\">COMPLETADA</span>",
                            "EN_CURSO": "<span class=\"badge-warning\">EN_CURSO</span>",
                            "PROGRAMADA": "<span class=\"badge-info\">PROGRAMADA</span>"
                        };
                        return badges[val] || "<span class=\"badge-info\">" + val + "</span>";
                    }'
                ]
            ];
        @endphp
        
        <x-interactive-table 
            :headers="$tableHeaders"
            :data="$tableData"
            :searchable="true"
            :sortable="true"
            :paginate="true"
            :perPage="10"
            :columnToggle="true"
        />
    </div>
</div>
@endsection
