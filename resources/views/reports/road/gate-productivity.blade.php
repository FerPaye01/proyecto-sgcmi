@extends('layouts.app')

@section('title', 'R6 - Productividad de Gates')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">
            R6 - Productividad de Gates
        </h2>
        <p class="text-gray-600 mb-4">
            Análisis de productividad de gates basado en eventos de entrada y salida de camiones
        </p>

        <!-- Filtros -->
        <x-filter-panel :showGate="true">
            <form method="GET" action="{{ route('reports.r6') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
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

                <!-- Gate -->
                <div>
                    <label for="gate_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Gate
                    </label>
                    <select 
                        id="gate_id" 
                        name="gate_id"
                        class="input-field"
                    >
                        <option value="">Todos</option>
                        @foreach($gates as $gate)
                            <option value="{{ $gate->id }}" {{ ($filters['gate_id'] ?? '') == $gate->id ? 'selected' : '' }}>
                                {{ $gate->name }} ({{ $gate->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Capacidad Teórica -->
                <div>
                    <label for="capacidad_teorica" class="block text-sm font-medium text-gray-700 mb-1">
                        Capacidad Teórica (veh/h)
                    </label>
                    <input 
                        type="number" 
                        id="capacidad_teorica" 
                        name="capacidad_teorica"
                        value="{{ $filters['capacidad_teorica'] ?? 10 }}"
                        min="1"
                        max="100"
                        class="input-field"
                    >
                </div>

                <!-- Botones -->
                <div class="md:col-span-4 flex gap-2">
                    <button type="submit" class="btn-primary">
                        Aplicar Filtros
                    </button>
                    <a href="{{ route('reports.r6') }}" class="btn-secondary">
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
            <h3 class="text-sm font-medium text-gray-600 mb-2">Vehículos por Hora</h3>
            <p class="text-3xl font-bold text-sgcmi-blue-900">
                {{ number_format($kpis['veh_x_hora'], 2) }}
            </p>
            <p class="text-xs text-gray-500 mt-1">Promedio de todas las horas</p>
        </div>

        <div class="card">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Tiempo de Ciclo</h3>
            <p class="text-3xl font-bold text-blue-600">
                {{ number_format($kpis['tiempo_ciclo_min'], 2) }} min
            </p>
            <p class="text-xs text-gray-500 mt-1">Entrada → Salida promedio</p>
        </div>

        <div class="card">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Picos vs Capacidad</h3>
            <p class="text-3xl font-bold {{ $kpis['picos_vs_capacidad'] > 50 ? 'text-red-600' : 'text-green-600' }}">
                {{ number_format($kpis['picos_vs_capacidad'], 2) }}%
            </p>
            <p class="text-xs text-gray-500 mt-1">Horas > 80% capacidad</p>
        </div>

        <div class="card">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Horas Pico</h3>
            <p class="text-3xl font-bold text-orange-600">
                {{ count($kpis['horas_pico']) }}
            </p>
            <p class="text-xs text-gray-500 mt-1">Horas con alta demanda</p>
        </div>
    </div>

    <!-- Gráfico de Productividad por Hora con Chart.js -->
    @if(count($productividad_por_hora) > 0)
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-xl font-semibold mb-4">Gráfico de Productividad por Hora del Día</h3>
            
            @foreach($productividad_por_hora as $gate => $horas)
                <div class="mb-8">
                    <h4 class="text-lg font-medium text-gray-800 mb-4">{{ $gate }}</h4>
                    
                    <!-- Canvas para Chart.js -->
                    <div class="relative" style="height: 400px;">
                        <canvas id="chart-{{ Str::slug($gate) }}"></canvas>
                    </div>
                    
                    <!-- Leyenda -->
                    <div class="flex justify-center gap-6 mt-6 text-sm">
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 bg-red-600 rounded"></div>
                            <span>Pico (>80% capacidad)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 bg-yellow-500 rounded"></div>
                            <span>Alta (50-80%)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 bg-green-500 rounded"></div>
                            <span>Normal (<50%)</span>
                        </div>
                    </div>
                    
                    <!-- Script para inicializar el gráfico -->
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const ctx = document.getElementById('chart-{{ Str::slug($gate) }}');
                            if (!ctx) return;
                            
                            const labels = @json(array_keys($horas));
                            const data = @json(array_map(fn($h) => $h['veh_x_hora'], $horas));
                            const capacidadTeorica = {{ $filters['capacidad_teorica'] ?? 10 }};
                            
                            // Asignar colores según el nivel de productividad
                            const backgroundColors = data.map(value => {
                                const porcentaje = (value / capacidadTeorica) * 100;
                                if (porcentaje > 80) return 'rgba(220, 38, 38, 0.8)'; // Rojo (Pico)
                                if (porcentaje >= 50) return 'rgba(234, 179, 8, 0.8)'; // Amarillo (Alta)
                                return 'rgba(34, 197, 94, 0.8)'; // Verde (Normal)
                            });
                            
                            const borderColors = data.map(value => {
                                const porcentaje = (value / capacidadTeorica) * 100;
                                if (porcentaje > 80) return 'rgba(220, 38, 38, 1)';
                                if (porcentaje >= 50) return 'rgba(234, 179, 8, 1)';
                                return 'rgba(34, 197, 94, 1)';
                            });
                            
                            new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels: labels,
                                    datasets: [{
                                        label: 'Vehículos por Hora',
                                        data: data,
                                        backgroundColor: backgroundColors,
                                        borderColor: borderColors,
                                        borderWidth: 1
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            display: false
                                        },
                                        tooltip: {
                                            callbacks: {
                                                label: function(context) {
                                                    const porcentaje = (context.parsed.y / capacidadTeorica) * 100;
                                                    return [
                                                        'Vehículos: ' + context.parsed.y,
                                                        'Capacidad: ' + porcentaje.toFixed(2) + '%'
                                                    ];
                                                }
                                            }
                                        },
                                        annotation: {
                                            annotations: {
                                                line1: {
                                                    type: 'line',
                                                    yMin: capacidadTeorica * 0.8,
                                                    yMax: capacidadTeorica * 0.8,
                                                    borderColor: 'rgba(220, 38, 38, 0.5)',
                                                    borderWidth: 2,
                                                    borderDash: [5, 5],
                                                    label: {
                                                        content: 'Umbral de pico (80%)',
                                                        enabled: true,
                                                        position: 'end'
                                                    }
                                                },
                                                line2: {
                                                    type: 'line',
                                                    yMin: capacidadTeorica,
                                                    yMax: capacidadTeorica,
                                                    borderColor: 'rgba(220, 38, 38, 0.8)',
                                                    borderWidth: 2,
                                                    label: {
                                                        content: 'Capacidad teórica',
                                                        enabled: true,
                                                        position: 'end'
                                                    }
                                                }
                                            }
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            max: Math.max(capacidadTeorica * 1.2, Math.max(...data) * 1.1),
                                            ticks: {
                                                callback: function(value) {
                                                    return value + ' veh';
                                                }
                                            },
                                            title: {
                                                display: true,
                                                text: 'Vehículos por Hora'
                                            }
                                        },
                                        x: {
                                            title: {
                                                display: true,
                                                text: 'Hora del Día'
                                            }
                                        }
                                    }
                                }
                            });
                        });
                    </script>
                </div>
            @endforeach
        </div>
        
        <!-- Tabla Detallada de Productividad por Hora -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-xl font-semibold mb-4">Detalle de Productividad por Hora del Día</h3>
            
            @foreach($productividad_por_hora as $gate => $horas)
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-800 mb-3">{{ $gate }}</h4>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="table-header">
                                <tr>
                                    <th class="px-4 py-2 text-left">Hora</th>
                                    <th class="px-4 py-2 text-left">Vehículos/Hora</th>
                                    <th class="px-4 py-2 text-left">Entradas</th>
                                    <th class="px-4 py-2 text-left">Salidas</th>
                                    <th class="px-4 py-2 text-left">% Capacidad</th>
                                    <th class="px-4 py-2 text-left">Barra Visual</th>
                                    <th class="px-4 py-2 text-left">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($horas as $hora => $datos)
                                    @php
                                        $capacidadTeorica = $filters['capacidad_teorica'] ?? 10;
                                        $porcentajeCapacidad = ($datos['veh_x_hora'] / $capacidadTeorica) * 100;
                                    @endphp
                                    <tr class="table-row">
                                        <td class="px-4 py-2 font-medium">{{ $hora }}</td>
                                        <td class="px-4 py-2 font-bold text-sgcmi-blue-900">{{ $datos['veh_x_hora'] }}</td>
                                        <td class="px-4 py-2 text-green-600">{{ $datos['entradas'] }}</td>
                                        <td class="px-4 py-2 text-red-600">{{ $datos['salidas'] }}</td>
                                        <td class="px-4 py-2 font-medium">{{ number_format($porcentajeCapacidad, 2) }}%</td>
                                        <td class="px-4 py-2">
                                            <div class="flex items-center">
                                                <div class="w-full bg-gray-200 rounded-full h-4 mr-2">
                                                    <div 
                                                        class="h-4 rounded-full {{ $porcentajeCapacidad > 80 ? 'bg-red-600' : ($porcentajeCapacidad >= 50 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                                        style="width: {{ min(100, $porcentajeCapacidad) }}%"
                                                    ></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2">
                                            @if($porcentajeCapacidad > 80)
                                                <span class="badge-danger">Pico</span>
                                            @elseif($porcentajeCapacidad >= 50)
                                                <span class="badge-warning">Alta</span>
                                            @elseif($datos['veh_x_hora'] > 0)
                                                <span class="badge-success">Normal</span>
                                            @else
                                                <span class="badge-info">Sin actividad</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Horas Pico Identificadas -->
    @if(count($kpis['horas_pico']) > 0)
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-xl font-semibold mb-4">Horas Pico Identificadas (>80% Capacidad)</h3>
            
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="table-header">
                        <tr>
                            <th class="px-4 py-2 text-left">Gate</th>
                            <th class="px-4 py-2 text-left">Hora</th>
                            <th class="px-4 py-2 text-left">Vehículos</th>
                            <th class="px-4 py-2 text-left">% Capacidad</th>
                            <th class="px-4 py-2 text-left">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($kpis['horas_pico'] as $pico)
                            <tr class="table-row">
                                <td class="px-4 py-2 font-medium">{{ $pico['gate'] }}</td>
                                <td class="px-4 py-2">{{ $pico['hora'] }}</td>
                                <td class="px-4 py-2 font-bold text-red-600">{{ $pico['vehiculos'] }}</td>
                                <td class="px-4 py-2 font-bold text-red-600">{{ number_format($pico['porcentaje'], 2) }}%</td>
                                <td class="px-4 py-2">
                                    <span class="badge-danger">Hora Pico</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-sm text-red-800">
                    <strong>⚠️ Alerta:</strong> Se han identificado {{ count($kpis['horas_pico']) }} horas con demanda superior al 80% de la capacidad teórica. 
                    Considere ajustar recursos o capacidad durante estos períodos.
                </p>
            </div>
        </div>
    @endif

    <!-- Detalle de Eventos -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-xl font-semibold mb-4">Detalle de Eventos de Gate</h3>
        
        @if($data->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="table-header">
                        <tr>
                            <th class="px-4 py-2 text-left">Timestamp</th>
                            <th class="px-4 py-2 text-left">Gate</th>
                            <th class="px-4 py-2 text-left">Camión</th>
                            <th class="px-4 py-2 text-left">Acción</th>
                            <th class="px-4 py-2 text-left">Cita ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data->take(100) as $evento)
                            <tr class="table-row">
                                <td class="px-4 py-2">{{ $evento->event_ts->format('Y-m-d H:i:s') }}</td>
                                <td class="px-4 py-2">{{ $evento->gate->name ?? 'N/A' }}</td>
                                <td class="px-4 py-2">{{ $evento->truck->placa ?? 'N/A' }}</td>
                                <td class="px-4 py-2">
                                    @if($evento->action === 'ENTRADA')
                                        <span class="badge-success">{{ $evento->action }}</span>
                                    @else
                                        <span class="badge-info">{{ $evento->action }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2">{{ $evento->cita_id ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            @if($data->count() > 100)
                <div class="mt-4 text-sm text-gray-600">
                    Mostrando los primeros 100 eventos de {{ $data->count() }} totales. Use los filtros para refinar la búsqueda.
                </div>
            @endif
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
            <li><strong>Vehículos por Hora:</strong> Promedio de vehículos procesados por hora en el período seleccionado</li>
            <li><strong>Tiempo de Ciclo:</strong> Tiempo promedio desde la entrada hasta la salida de un camión (en minutos)</li>
            <li><strong>Picos vs Capacidad:</strong> Porcentaje de horas que superan el 80% de la capacidad teórica</li>
            <li><strong>Horas Pico:</strong> Horas específicas donde la demanda supera el 80% de la capacidad teórica</li>
            <li><strong>Capacidad Teórica:</strong> Número máximo de vehículos que un gate puede procesar por hora (configurable en filtros)</li>
            <li><strong>Estado Pico:</strong> Demanda > 80% de capacidad (requiere atención)</li>
            <li><strong>Estado Alta:</strong> Demanda entre 50% y 80% de capacidad</li>
            <li><strong>Estado Normal:</strong> Demanda < 50% de capacidad</li>
        </ul>
    </div>
</div>
@endsection
