@extends('layouts.app')

@section('title', 'Reporte R12 - Cumplimiento de SLAs')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Reporte R12: Cumplimiento de SLAs</h1>
        <p class="text-gray-600">Análisis del cumplimiento de acuerdos de nivel de servicio por actor (empresa, entidad)</p>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8" x-data="reportFilters()">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Filtros</h2>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="fecha_desde" class="block text-sm font-medium text-gray-700 mb-2">Fecha Desde</label>
                <input type="date" id="fecha_desde" name="fecha_desde" value="{{ $filters['fecha_desde'] ?? '' }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div>
                <label for="fecha_hasta" class="block text-sm font-medium text-gray-700 mb-2">Fecha Hasta</label>
                <input type="date" id="fecha_hasta" name="fecha_hasta" value="{{ $filters['fecha_hasta'] ?? '' }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Aplicar Filtros</button>
                <a href="{{ request()->url() }}" class="flex-1 bg-gray-400 text-white px-4 py-2 rounded-lg hover:bg-gray-500 transition text-center">Limpiar</a>
            </div>
        </form>
    </div>

    <!-- KPIs Consolidados -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="text-sm font-medium text-gray-600 mb-2">Total de Actores</div>
            <div class="text-3xl font-bold text-blue-600">{{ $kpis['total_actores'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="text-sm font-medium text-gray-600 mb-2">Cumplimiento Promedio</div>
            <div class="text-3xl font-bold text-green-600">{{ $kpis['pct_cumplimiento_promedio'] }}%</div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="text-sm font-medium text-gray-600 mb-2">Actores Excelentes</div>
            <div class="text-3xl font-bold text-green-600">{{ $kpis['actores_excelentes'] }}</div>
            <div class="text-xs text-gray-500 mt-1">{{ $kpis['pct_actores_excelentes'] }}%</div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="text-sm font-medium text-gray-600 mb-2">Actores Críticos</div>
            <div class="text-3xl font-bold text-red-600">{{ $kpis['actores_críticos'] }}</div>
            <div class="text-xs text-gray-500 mt-1">{{ $kpis['pct_actores_críticos'] }}%</div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="text-sm font-medium text-gray-600 mb-2">Penalidades Totales</div>
            <div class="text-3xl font-bold text-orange-600">{{ $kpis['penalidades_totales'] }}%</div>
        </div>
    </div>

    <!-- Tabla de Cumplimiento por Actor -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Cumplimiento de SLAs por Actor</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Actor</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Tipo</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">SLAs Cumplidos</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">% Cumplimiento</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Penalidades</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($por_actor as $actor)
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-800 font-medium">{{ $actor['actor_name'] }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $actor['actor_tipo'] === 'TRANSPORTISTA' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                    {{ $actor['actor_tipo'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-center text-gray-800">
                                {{ $actor['slas_cumplidos'] }} / {{ $actor['total_slas'] }}
                            </td>
                            <td class="px-6 py-4 text-sm text-center">
                                <div class="flex items-center justify-center">
                                    <div class="w-24 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $actor['pct_cumplimiento'] }}%"></div>
                                    </div>
                                    <span class="font-semibold text-gray-800">{{ $actor['pct_cumplimiento'] }}%</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-center text-orange-600 font-semibold">
                                {{ $actor['penalidades_totales'] }}%
                            </td>
                            <td class="px-6 py-4 text-sm text-center">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    @if($actor['estado'] === 'EXCELENTE') bg-green-100 text-green-800
                                    @elseif($actor['estado'] === 'BUENO') bg-blue-100 text-blue-800
                                    @elseif($actor['estado'] === 'REGULAR') bg-yellow-100 text-yellow-800
                                    @else bg-red-100 text-red-800
                                    @endif
                                ">
                                    {{ $actor['estado'] }}
                                </span>
                            </td>
                        </tr>
                        <!-- Fila expandible con detalles de SLAs -->
                        <tr class="bg-gray-50 hidden" id="details-{{ $actor['actor_id'] }}">
                            <td colspan="6" class="px-6 py-4">
                                <div class="space-y-2">
                                    <h4 class="font-semibold text-gray-800 mb-3">Detalle de SLAs:</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        @foreach($actor['slas'] as $sla)
                                            <div class="border border-gray-300 rounded-lg p-3">
                                                <div class="flex justify-between items-start mb-2">
                                                    <div>
                                                        <div class="font-semibold text-gray-800">{{ $sla['sla_name'] }}</div>
                                                        <div class="text-xs text-gray-600">{{ $sla['sla_code'] }}</div>
                                                    </div>
                                                    <span class="px-2 py-1 rounded text-xs font-semibold {{ $sla['cumple'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                        {{ $sla['cumple'] ? 'CUMPLE' : 'INCUMPLE' }}
                                                    </span>
                                                </div>
                                                <div class="text-sm text-gray-700">
                                                    <div>Valor: <span class="font-semibold">{{ $sla['valor'] }}</span></div>
                                                    <div>Umbral: <span class="font-semibold">{{ $sla['umbral'] }}</span> ({{ $sla['comparador'] }})</div>
                                                    @if(!$sla['cumple'])
                                                        <div class="text-orange-600 mt-1">Penalidad: <span class="font-semibold">{{ $sla['penalidad'] }}%</span></div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                No hay datos disponibles para el período seleccionado
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Resumen de Estados -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-8">
        <div class="bg-green-50 rounded-lg shadow-md p-6 border-l-4 border-green-600">
            <div class="text-sm font-medium text-gray-600 mb-2">Excelentes</div>
            <div class="text-2xl font-bold text-green-600">{{ $kpis['actores_excelentes'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Cumplimiento ≥ 90%</div>
        </div>
        <div class="bg-blue-50 rounded-lg shadow-md p-6 border-l-4 border-blue-600">
            <div class="text-sm font-medium text-gray-600 mb-2">Buenos</div>
            <div class="text-2xl font-bold text-blue-600">{{ $kpis['actores_buenos'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Cumplimiento 75-89%</div>
        </div>
        <div class="bg-yellow-50 rounded-lg shadow-md p-6 border-l-4 border-yellow-600">
            <div class="text-sm font-medium text-gray-600 mb-2">Regulares</div>
            <div class="text-2xl font-bold text-yellow-600">{{ $kpis['actores_regulares'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Cumplimiento 50-74%</div>
        </div>
        <div class="bg-red-50 rounded-lg shadow-md p-6 border-l-4 border-red-600">
            <div class="text-sm font-medium text-gray-600 mb-2">Críticos</div>
            <div class="text-2xl font-bold text-red-600">{{ $kpis['actores_críticos'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Cumplimiento < 50%</div>
        </div>
    </div>
</div>

<script>
    function reportFilters() {
        return {
            toggleDetails(actorId) {
                const element = document.getElementById(`details-${actorId}`);
                if (element) {
                    element.classList.toggle('hidden');
                }
            }
        };
    }

    // Agregar event listeners a las filas para expandir detalles
    document.querySelectorAll('tbody tr:not([id^="details-"])').forEach(row => {
        row.addEventListener('click', function() {
            const actorId = this.querySelector('td:first-child').textContent.trim();
            // Buscar el actor_id en los datos
            const detailsRow = this.nextElementSibling;
            if (detailsRow && detailsRow.id.startsWith('details-')) {
                detailsRow.classList.toggle('hidden');
            }
        });
    });
</script>
@endsection
