@extends('layouts.app')

@section('title', 'R11 - Alertas Tempranas')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Reporte R11: Alertas Tempranas</h1>
        <p class="text-gray-600 mt-2">Detección de condiciones de riesgo operacional</p>
    </div>

    <!-- Estado General del Sistema -->
    <div class="mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Estado General del Sistema</h2>
            
            <div class="flex items-center justify-center">
                @if($estado_general === 'ROJO')
                    <div class="text-center">
                        <div class="w-24 h-24 rounded-full bg-red-500 flex items-center justify-center mx-auto mb-4">
                            <span class="text-white text-4xl font-bold">!</span>
                        </div>
                        <p class="text-2xl font-bold text-red-600">CRÍTICO</p>
                        <p class="text-gray-600 mt-2">Se han detectado alertas críticas en el sistema</p>
                    </div>
                @elseif($estado_general === 'AMARILLO')
                    <div class="text-center">
                        <div class="w-24 h-24 rounded-full bg-yellow-500 flex items-center justify-center mx-auto mb-4">
                            <span class="text-white text-4xl font-bold">⚠</span>
                        </div>
                        <p class="text-2xl font-bold text-yellow-600">PRECAUCIÓN</p>
                        <p class="text-gray-600 mt-2">Se han detectado alertas de precaución en el sistema</p>
                    </div>
                @else
                    <div class="text-center">
                        <div class="w-24 h-24 rounded-full bg-green-500 flex items-center justify-center mx-auto mb-4">
                            <span class="text-white text-4xl font-bold">✓</span>
                        </div>
                        <p class="text-2xl font-bold text-green-600">NORMAL</p>
                        <p class="text-gray-600 mt-2">El sistema está operando dentro de los parámetros normales</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- KPIs de Alertas -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-gray-600 text-sm font-semibold">Total de Alertas</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $kpis['total_alertas'] }}</p>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-gray-600 text-sm font-semibold">Alertas Críticas (Rojo)</p>
            <p class="text-3xl font-bold text-red-600 mt-2">{{ $kpis['alertas_rojas'] }}</p>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-gray-600 text-sm font-semibold">Alertas de Precaución (Amarillo)</p>
            <p class="text-3xl font-bold text-yellow-600 mt-2">{{ $kpis['alertas_amarillas'] }}</p>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-gray-600 text-sm font-semibold">% Alertas Críticas</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $kpis['pct_alertas_críticas'] }}%</p>
        </div>
    </div>

    <!-- Desglose por Tipo de Alerta -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-gray-600 text-sm font-semibold">Alertas de Congestión de Muelles</p>
            <p class="text-3xl font-bold text-blue-600 mt-2">{{ $kpis['alertas_congestión'] }}</p>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-gray-600 text-sm font-semibold">Alertas de Acumulación de Camiones</p>
            <p class="text-3xl font-bold text-purple-600 mt-2">{{ $kpis['alertas_acumulación'] }}</p>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Filtros</h2>
        
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Desde</label>
                <input type="datetime-local" name="fecha_desde" value="{{ $filters['fecha_desde'] ?? '' }}" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Hasta</label>
                <input type="datetime-local" name="fecha_hasta" value="{{ $filters['fecha_hasta'] ?? '' }}" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Umbral Congestión (%)</label>
                <input type="number" name="umbral_congestión" value="{{ $filters['umbral_congestión'] ?? 85 }}" step="0.1"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Umbral Acumulación (h)</label>
                <input type="number" name="umbral_acumulación" value="{{ $filters['umbral_acumulación'] ?? 4 }}" step="0.1"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500">
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    Aplicar Filtros
                </button>
            </div>
        </form>
    </div>

    <!-- Listado de Alertas -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Alertas Detectadas</h2>
        
        @if($alertas->isEmpty())
            <div class="text-center py-8">
                <p class="text-gray-600">No se han detectado alertas en el periodo especificado</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($alertas as $alerta)
                    <div class="border-l-4 p-4 rounded-md
                        @if($alerta['nivel'] === 'ROJO')
                            border-red-500 bg-red-50
                        @elseif($alerta['nivel'] === 'AMARILLO')
                            border-yellow-500 bg-yellow-50
                        @else
                            border-green-500 bg-green-50
                        @endif
                    ">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold
                                        @if($alerta['nivel'] === 'ROJO')
                                            bg-red-200 text-red-800
                                        @elseif($alerta['nivel'] === 'AMARILLO')
                                            bg-yellow-200 text-yellow-800
                                        @else
                                            bg-green-200 text-green-800
                                        @endif
                                    ">
                                        {{ $alerta['nivel'] }}
                                    </span>
                                    <span class="text-sm font-semibold text-gray-700">{{ $alerta['tipo'] }}</span>
                                </div>
                                
                                <p class="text-gray-900 font-semibold mb-2">{{ $alerta['descripción'] }}</p>
                                
                                <div class="grid grid-cols-2 gap-4 mb-3 text-sm">
                                    <div>
                                        <p class="text-gray-600">Valor Actual</p>
                                        <p class="font-semibold text-gray-900">{{ $alerta['valor'] }} {{ $alerta['unidad'] }}</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-600">Umbral</p>
                                        <p class="font-semibold text-gray-900">{{ $alerta['umbral'] }} {{ $alerta['unidad'] }}</p>
                                    </div>
                                </div>
                                
                                @if(isset($alerta['citas_afectadas']))
                                    <div class="mb-3 text-sm">
                                        <p class="text-gray-600">Citas Afectadas</p>
                                        <p class="font-semibold text-gray-900">{{ $alerta['citas_afectadas'] }}</p>
                                    </div>
                                @endif
                                
                                <div class="mb-2">
                                    <p class="text-sm font-semibold text-gray-700 mb-2">Acciones Recomendadas:</p>
                                    <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                                        @foreach($alerta['acciones_recomendadas'] as $accion)
                                            <li>{{ $accion }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                
                                <p class="text-xs text-gray-500 mt-2">Detectado: {{ $alerta['timestamp'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Información de Ayuda -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-8">
        <h3 class="text-lg font-semibold text-blue-900 mb-2">Información sobre Alertas</h3>
        <ul class="text-sm text-blue-800 space-y-2">
            <li><strong>Congestión de Muelles:</strong> Se detecta cuando la utilización de un muelle supera el 85%</li>
            <li><strong>Acumulación de Camiones:</strong> Se detecta cuando el tiempo de espera promedio supera las 4 horas</li>
            <li><strong>Nivel ROJO:</strong> Valor > 1.5x el umbral (situación crítica)</li>
            <li><strong>Nivel AMARILLO:</strong> Valor entre umbral y 1.5x el umbral (precaución)</li>
            <li><strong>Nivel VERDE:</strong> Valor por debajo del umbral (normal)</li>
        </ul>
    </div>
</div>

<script>
    // Auto-refresh de alertas cada 5 minutos
    setInterval(function() {
        fetch('{{ route("reports.r11.api") }}?fecha_desde={{ $filters["fecha_desde"] ?? "" }}&fecha_hasta={{ $filters["fecha_hasta"] ?? "" }}')
            .then(response => response.json())
            .then(data => {
                // Actualizar KPIs
                document.querySelectorAll('[data-kpi]').forEach(el => {
                    const kpi = el.dataset.kpi;
                    if (data.kpis[kpi] !== undefined) {
                        el.textContent = data.kpis[kpi];
                    }
                });
            });
    }, 5 * 60 * 1000); // 5 minutos
</script>
@endsection
