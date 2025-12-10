@extends('layouts.app')

@section('title', 'R3 - Utilización de Muelles')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">
            R3 - Utilización de Muelles
        </h2>
        <p class="text-gray-600 mb-4">
            Análisis de utilización horaria de muelles basado en tiempos de atraque (ATB) y salida (ATD)
        </p>

        <!-- Filtros -->
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

                <!-- Franja Horaria -->
                <div>
                    <label for="franja_horas" class="block text-sm font-medium text-gray-700 mb-1">
                        Franja (horas)
                    </label>
                    <select 
                        id="franja_horas" 
                        name="franja_horas"
                        class="input-field"
                    >
                        <option value="1" {{ ($filters['franja_horas'] ?? 1) == 1 ? 'selected' : '' }}>1 hora</option>
                        <option value="2" {{ ($filters['franja_horas'] ?? 1) == 2 ? 'selected' : '' }}>2 horas</option>
                        <option value="4" {{ ($filters['franja_horas'] ?? 1) == 4 ? 'selected' : '' }}>4 horas</option>
                        <option value="6" {{ ($filters['franja_horas'] ?? 1) == 6 ? 'selected' : '' }}>6 horas</option>
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
            <h3 class="text-sm font-medium text-gray-600 mb-2">Utilización Promedio</h3>
            <p class="text-3xl font-bold text-sgcmi-blue-900">
                {{ number_format($kpis['utilizacion_promedio'], 2) }}%
            </p>
            <p class="text-xs text-gray-500 mt-1">Promedio de todas las franjas</p>
        </div>

        <div class="card">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Conflictos de Ventana</h3>
            <p class="text-3xl font-bold {{ $kpis['conflictos_ventana'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                {{ $kpis['conflictos_ventana'] }}
            </p>
            <p class="text-xs text-gray-500 mt-1">Solapamientos detectados</p>
        </div>

        <div class="card">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Horas Ociosas</h3>
            <p class="text-3xl font-bold text-yellow-600">
                {{ number_format($kpis['horas_ociosas'], 2) }}h
            </p>
            <p class="text-xs text-gray-500 mt-1">Utilización < 10%</p>
        </div>

        <div class="card">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Utilización Máxima</h3>
            <p class="text-3xl font-bold text-sgcmi-blue-900">
                {{ number_format($kpis['utilizacion_maxima'], 2) }}%
            </p>
            <p class="text-xs text-gray-500 mt-1">Pico de utilización</p>
        </div>
    </div>

    <!-- Gráfico de Barras de Utilización con Chart.js -->
    @if(count($utilizacion_por_franja) > 0)
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-xl font-semibold mb-4">Gráfico de Utilización por Muelle</h3>
            
            @foreach($utilizacion_por_franja as $muelle => $franjas)
                <div class="mb-8">
                    <h4 class="text-lg font-medium text-gray-800 mb-4">{{ $muelle }}</h4>
                    
                    <!-- Canvas para Chart.js -->
                    <div class="relative" style="height: 400px;">
                        <canvas id="chart-{{ Str::slug($muelle) }}"></canvas>
                    </div>
                    
                    <!-- Leyenda -->
                    <div class="flex justify-center gap-6 mt-6 text-sm">
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 bg-red-600 rounded"></div>
                            <span>Alta (≥85%)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 bg-yellow-500 rounded"></div>
                            <span>Media (50-85%)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 bg-green-500 rounded"></div>
                            <span>Baja (10-50%)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 bg-slate-400 rounded"></div>
                            <span>Ociosa (<10%)</span>
                        </div>
                    </div>
                    
                    <!-- Script para inicializar el gráfico -->
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const ctx = document.getElementById('chart-{{ Str::slug($muelle) }}');
                            if (!ctx) return;
                            
                            const labels = @json(array_keys($franjas));
                            const data = @json(array_values($franjas));
                            
                            // Formatear etiquetas de fecha
                            const formattedLabels = labels.map(label => {
                                const date = new Date(label);
                                return date.toLocaleDateString('es-PE', { 
                                    day: '2-digit', 
                                    month: '2-digit',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                });
                            });
                            
                            // Asignar colores según el nivel de utilización
                            const backgroundColors = data.map(value => {
                                if (value >= 85) return 'rgba(220, 38, 38, 0.8)'; // Rojo (Alta)
                                if (value >= 50) return 'rgba(234, 179, 8, 0.8)'; // Amarillo (Media)
                                if (value >= 10) return 'rgba(34, 197, 94, 0.8)'; // Verde (Baja)
                                return 'rgba(148, 163, 184, 0.8)'; // Gris (Ociosa)
                            });
                            
                            const borderColors = data.map(value => {
                                if (value >= 85) return 'rgba(220, 38, 38, 1)';
                                if (value >= 50) return 'rgba(234, 179, 8, 1)';
                                if (value >= 10) return 'rgba(34, 197, 94, 1)';
                                return 'rgba(148, 163, 184, 1)';
                            });
                            
                            new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels: formattedLabels,
                                    datasets: [{
                                        label: 'Utilización (%)',
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
                                                    return 'Utilización: ' + context.parsed.y.toFixed(2) + '%';
                                                }
                                            }
                                        },
                                        annotation: {
                                            annotations: {
                                                line1: {
                                                    type: 'line',
                                                    yMin: 85,
                                                    yMax: 85,
                                                    borderColor: 'rgba(220, 38, 38, 0.5)',
                                                    borderWidth: 2,
                                                    borderDash: [5, 5],
                                                    label: {
                                                        content: 'Umbral de alerta (85%)',
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
                                            max: 100,
                                            ticks: {
                                                callback: function(value) {
                                                    return value + '%';
                                                }
                                            },
                                            title: {
                                                display: true,
                                                text: 'Utilización (%)'
                                            }
                                        },
                                        x: {
                                            ticks: {
                                                maxRotation: 45,
                                                minRotation: 45
                                            },
                                            title: {
                                                display: true,
                                                text: 'Franja Horaria'
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
        
        <!-- Tabla Detallada de Utilización por Franja -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-xl font-semibold mb-4">Detalle de Utilización por Franja Horaria</h3>
            
            @foreach($utilizacion_por_franja as $muelle => $franjas)
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-800 mb-3">{{ $muelle }}</h4>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="table-header">
                                <tr>
                                    <th class="px-4 py-2 text-left">Franja Horaria</th>
                                    <th class="px-4 py-2 text-left">Utilización (%)</th>
                                    <th class="px-4 py-2 text-left">Barra Visual</th>
                                    <th class="px-4 py-2 text-left">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($franjas as $hora => $utilizacion)
                                    <tr class="table-row">
                                        <td class="px-4 py-2">{{ $hora }}</td>
                                        <td class="px-4 py-2 font-medium">{{ number_format($utilizacion, 2) }}%</td>
                                        <td class="px-4 py-2">
                                            <div class="flex items-center">
                                                <div class="w-full bg-gray-200 rounded-full h-4 mr-2">
                                                    <div 
                                                        class="h-4 rounded-full {{ $utilizacion >= 85 ? 'bg-red-600' : ($utilizacion >= 50 ? 'bg-yellow-500' : ($utilizacion >= 10 ? 'bg-green-500' : 'bg-slate-400')) }}"
                                                        style="width: {{ min(100, $utilizacion) }}%"
                                                    ></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2">
                                            @if($utilizacion >= 85)
                                                <span class="badge-danger">Alta</span>
                                            @elseif($utilizacion >= 50)
                                                <span class="badge-warning">Media</span>
                                            @elseif($utilizacion >= 10)
                                                <span class="badge-success">Baja</span>
                                            @else
                                                <span class="badge-info">Ociosa</span>
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

    <!-- Detalle de Llamadas - Tabla Interactiva -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-xl font-semibold mb-4">Detalle de Llamadas de Naves</h3>
        
        @if(isset($tableData) && count($tableData) > 0)
            <x-interactive-table 
                :headers="$tableHeaders"
                :data="$tableData"
                :searchable="true"
                :sortable="true"
                :paginate="true"
                :perPage="10"
                :columnToggle="true"
            />
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
            <li><strong>Utilización Promedio:</strong> Porcentaje promedio de ocupación de muelles en todas las franjas horarias</li>
            <li><strong>Conflictos de Ventana:</strong> Número de solapamientos detectados (dos naves en el mismo muelle al mismo tiempo)</li>
            <li><strong>Horas Ociosas:</strong> Total de horas con utilización menor al 10%</li>
            <li><strong>Utilización Máxima:</strong> Pico de utilización observado en cualquier franja</li>
            <li><strong>Estado Alta:</strong> Utilización ≥ 85% (riesgo de congestión)</li>
            <li><strong>Estado Media:</strong> Utilización entre 50% y 85%</li>
            <li><strong>Estado Baja:</strong> Utilización entre 10% y 50%</li>
            <li><strong>Estado Ociosa:</strong> Utilización < 10%</li>
        </ul>
    </div>
</div>
@endsection
