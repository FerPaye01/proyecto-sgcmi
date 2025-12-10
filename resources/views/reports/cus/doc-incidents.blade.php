@extends('layouts.app')

@section('title', 'R9 - Incidencias de Documentación')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">R9 - Incidencias de Documentación</h1>
        <div class="flex gap-2">
            <button @click="$dispatch('export', {format: 'csv'})" class="btn-secondary">
                Exportar CSV
            </button>
            <button @click="$dispatch('export', {format: 'xlsx'})" class="btn-secondary">
                Exportar Excel
            </button>
            <button @click="$dispatch('export', {format: 'pdf'})" class="btn-secondary">
                Exportar PDF
            </button>
        </div>
    </div>

    <!-- Filtros -->
    <x-filter-panel :showEstado="false" :showBerth="false" :showVessel="false" :showCompany="false" :showGate="false">
        <div class="col-span-2">
            <label for="regimen" class="block text-sm font-medium text-gray-700 mb-1">
                Régimen
            </label>
            <select id="regimen" name="regimen" class="input-field">
                <option value="">Todos</option>
                @foreach($regimenes as $key => $label)
                    <option value="{{ $key }}" {{ ($filters['regimen'] ?? '') == $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
        
        <div class="col-span-2">
            <label for="entidad_id" class="block text-sm font-medium text-gray-700 mb-1">
                Entidad Aduanera
            </label>
            <select id="entidad_id" name="entidad_id" class="input-field">
                <option value="">Todas</option>
                @foreach($entidades as $entidad)
                    <option value="{{ $entidad->id }}" {{ ($filters['entidad_id'] ?? '') == $entidad->id ? 'selected' : '' }}>
                        {{ $entidad->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </x-filter-panel>

    <!-- KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="card">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Total Trámites</h3>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($kpis['total_tramites']) }}</p>
        </div>
        
        <div class="card">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Rechazos</h3>
            <p class="text-3xl font-bold text-red-600">{{ number_format($kpis['rechazos']) }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ number_format($kpis['pct_rechazos'], 1) }}%</p>
        </div>
        
        <div class="card">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Reprocesos</h3>
            <p class="text-3xl font-bold text-yellow-600">{{ number_format($kpis['reprocesos']) }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ number_format($kpis['pct_reprocesos'], 1) }}%</p>
        </div>
        
        <div class="card">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Tiempo Subsanación</h3>
            <p class="text-3xl font-bold text-blue-600">{{ number_format($kpis['tiempo_subsanacion_promedio_h'], 1) }}</p>
            <p class="text-sm text-gray-500 mt-1">horas promedio</p>
        </div>
    </div>

    <!-- Estadísticas por Entidad -->
    @if($por_entidad->isNotEmpty())
    <div class="card mb-6">
        <h2 class="text-xl font-semibold mb-4">Estadísticas por Entidad Aduanera</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="table-header">
                    <tr>
                        <th class="px-4 py-3 text-left">Entidad</th>
                        <th class="px-4 py-3 text-right">Trámites</th>
                        <th class="px-4 py-3 text-right">Rechazos</th>
                        <th class="px-4 py-3 text-right">% Rechazos</th>
                        <th class="px-4 py-3 text-right">Reprocesos</th>
                        <th class="px-4 py-3 text-right">% Reprocesos</th>
                        <th class="px-4 py-3 text-right">Observaciones</th>
                        <th class="px-4 py-3 text-right">T. Subsanación (h)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($por_entidad as $stat)
                    <tr class="table-row">
                        <td class="px-4 py-3 font-medium">{{ $stat->entidad_name }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($stat->total_tramites) }}</td>
                        <td class="px-4 py-3 text-right">
                            <span class="badge-danger">{{ number_format($stat->rechazos) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">{{ number_format($stat->pct_rechazos, 1) }}%</td>
                        <td class="px-4 py-3 text-right">
                            <span class="badge-warning">{{ number_format($stat->reprocesos) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">{{ number_format($stat->pct_reprocesos, 1) }}%</td>
                        <td class="px-4 py-3 text-right">{{ number_format($stat->observaciones) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($stat->tiempo_subsanacion_promedio_h, 1) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Detalle de Trámites con Incidencias -->
    <div class="card">
        <h2 class="text-xl font-semibold mb-4">Detalle de Trámites con Incidencias</h2>
        
        @if($data->isEmpty())
            <div class="text-center py-8 text-gray-500">
                <p>No se encontraron trámites con incidencias en el período seleccionado.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="table-header">
                        <tr>
                            <th class="px-4 py-3 text-left">Trámite ID</th>
                            <th class="px-4 py-3 text-left">Régimen</th>
                            <th class="px-4 py-3 text-left">Estado</th>
                            <th class="px-4 py-3 text-left">Entidad</th>
                            <th class="px-4 py-3 text-center">Rechazo</th>
                            <th class="px-4 py-3 text-center">Reproceso</th>
                            <th class="px-4 py-3 text-right">Observaciones</th>
                            <th class="px-4 py-3 text-right">T. Subsanación (h)</th>
                            <th class="px-4 py-3 text-left">Fecha Inicio</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $tramite)
                        <tr class="table-row">
                            <td class="px-4 py-3">
                                <a href="{{ route('tramites.show', $tramite->id) }}" class="text-blue-600 hover:underline">
                                    {{ $tramite->tramite_ext_id }}
                                </a>
                            </td>
                            <td class="px-4 py-3">{{ $tramite->regimen }}</td>
                            <td class="px-4 py-3">
                                @if($tramite->estado === 'RECHAZADO')
                                    <span class="badge-danger">{{ $tramite->estado }}</span>
                                @elseif($tramite->estado === 'OBSERVADO')
                                    <span class="badge-warning">{{ $tramite->estado }}</span>
                                @elseif($tramite->estado === 'COMPLETO')
                                    <span class="badge-success">{{ $tramite->estado }}</span>
                                @else
                                    <span class="badge-info">{{ $tramite->estado }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $tramite->entidad?->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($tramite->tiene_rechazo)
                                    <span class="text-red-600 font-bold">✗</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($tramite->tiene_reproceso)
                                    <span class="text-yellow-600 font-bold">⟳</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">{{ $tramite->num_observaciones }}</td>
                            <td class="px-4 py-3 text-right">
                                @if($tramite->tiempo_subsanacion_h)
                                    {{ number_format($tramite->tiempo_subsanacion_h, 1) }}
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $tramite->fecha_inicio->format('d/m/Y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <div class="mt-4">
                {{ $data->links() }}
            </div>
        @endif
    </div>

    <!-- Leyenda -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 class="font-semibold text-blue-900 mb-2">Leyenda</h3>
        <ul class="text-sm text-blue-800 space-y-1">
            <li><strong>Rechazo (✗):</strong> Trámite con estado RECHAZADO</li>
            <li><strong>Reproceso (⟳):</strong> Trámite que volvió a EN_REVISION después de estar OBSERVADO</li>
            <li><strong>Observaciones:</strong> Número de veces que el trámite pasó a estado OBSERVADO</li>
            <li><strong>Tiempo Subsanación:</strong> Tiempo promedio desde OBSERVADO hasta el siguiente cambio de estado</li>
        </ul>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('exportHandler', () => ({
        init() {
            this.$el.addEventListener('export', (e) => {
                const format = e.detail.format;
                const params = new URLSearchParams(window.location.search);
                params.set('format', format);
                window.location.href = `/export/r9?${params.toString()}`;
            });
        }
    }));
});
</script>
@endpush
@endsection
