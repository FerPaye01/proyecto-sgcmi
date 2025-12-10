@extends('layouts.app')

@section('title', 'R8 - Tiempo de Despacho')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Reporte R8: Tiempo de Despacho por Régimen</h1>
        <p class="text-gray-600 mt-2">Análisis de tiempos de despacho aduanero con percentiles y umbrales</p>
    </div>

    <!-- KPIs Section -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 mb-1">Percentil 50 (Mediana)</div>
            <div class="text-3xl font-bold text-blue-600">{{ number_format($kpis['p50_horas'], 2) }}</div>
            <div class="text-xs text-gray-500 mt-1">horas de despacho</div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 mb-1">Percentil 90</div>
            <div class="text-3xl font-bold text-orange-600">{{ number_format($kpis['p90_horas'], 2) }}</div>
            <div class="text-xs text-gray-500 mt-1">horas de despacho</div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 mb-1">Promedio</div>
            <div class="text-3xl font-bold text-purple-600">{{ number_format($kpis['promedio_horas'], 2) }}</div>
            <div class="text-xs text-gray-500 mt-1">horas de despacho</div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 mb-1">Fuera de Umbral</div>
            <div class="text-3xl font-bold {{ $kpis['fuera_umbral_pct'] > 20 ? 'text-red-600' : 'text-green-600' }}">{{ number_format($kpis['fuera_umbral_pct'], 2) }}%</div>
            <div class="text-xs text-gray-500 mt-1">{{ $kpis['fuera_umbral'] }} de {{ $kpis['total_tramites'] }} trámites</div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 mb-1">Umbral Configurado</div>
            <div class="text-3xl font-bold text-gray-700">{{ $kpis['umbral_horas'] }}</div>
            <div class="text-xs text-gray-500 mt-1">horas máximas</div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Filtros</h2>
        
        <form method="GET" action="{{ route('reports.r8') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label for="fecha_desde" class="block text-sm font-medium text-gray-700 mb-2">Fecha Desde</label>
                <input 
                    type="date" 
                    id="fecha_desde" 
                    name="fecha_desde" 
                    value="{{ $filters['fecha_desde'] ?? '' }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>

            <div>
                <label for="fecha_hasta" class="block text-sm font-medium text-gray-700 mb-2">Fecha Hasta</label>
                <input 
                    type="date" 
                    id="fecha_hasta" 
                    name="fecha_hasta" 
                    value="{{ $filters['fecha_hasta'] ?? '' }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>

            <div>
                <label for="regimen" class="block text-sm font-medium text-gray-700 mb-2">Régimen</label>
                <select 
                    id="regimen" 
                    name="regimen"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">Todos los regímenes</option>
                    @foreach($regimenes as $key => $label)
                        <option value="{{ $key }}" {{ (isset($filters['regimen']) && $filters['regimen'] == $key) ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="entidad_id" class="block text-sm font-medium text-gray-700 mb-2">Entidad Aduanera</label>
                <select 
                    id="entidad_id" 
                    name="entidad_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">Todas las entidades</option>
                    @foreach($entidades as $entidad)
                        <option value="{{ $entidad->id }}" {{ (isset($filters['entidad_id']) && $filters['entidad_id'] == $entidad->id) ? 'selected' : '' }}>
                            {{ $entidad->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="umbral_horas" class="block text-sm font-medium text-gray-700 mb-2">Umbral (horas)</label>
                <input 
                    type="number" 
                    id="umbral_horas" 
                    name="umbral_horas" 
                    value="{{ $filters['umbral_horas'] ?? 24 }}"
                    min="1"
                    step="1"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>

            <div class="md:col-span-5 flex gap-2">
                <button 
                    type="submit"
                    class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    Aplicar Filtros
                </button>
                <a 
                    href="{{ route('reports.r8') }}"
                    class="px-6 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500"
                >
                    Limpiar Filtros
                </a>
            </div>
        </form>
    </div>

    <!-- Export Buttons -->
    @if(auth()->check() && auth()->user()->hasPermission('REPORT_EXPORT'))
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Exportar Reporte</h2>
        <div class="flex gap-3">
            <form method="POST" action="{{ route('export.report', ['report' => 'r8']) }}" class="inline">
                @csrf
                <input type="hidden" name="format" value="csv">
                @foreach($filters as $key => $value)
                    @if($value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <button 
                    type="submit"
                    class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 inline-flex items-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Exportar CSV
                </button>
            </form>

            <form method="POST" action="{{ route('export.report', ['report' => 'r8']) }}" class="inline">
                @csrf
                <input type="hidden" name="format" value="xlsx">
                @foreach($filters as $key => $value)
                    @if($value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <button 
                    type="submit"
                    class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 inline-flex items-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Exportar XLSX
                </button>
            </form>

            <form method="POST" action="{{ route('export.report', ['report' => 'r8']) }}" class="inline">
                @csrf
                <input type="hidden" name="format" value="pdf">
                @foreach($filters as $key => $value)
                    @if($value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <button 
                    type="submit"
                    class="px-6 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 inline-flex items-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    Exportar PDF
                </button>
            </form>
        </div>
        <p class="text-sm text-gray-500 mt-3">
            Los archivos se generarán con los filtros actualmente aplicados. Formato: reporte_r8_YYYY-MM-DD_HHMMSS.{ext}
        </p>
    </div>
    @endif

    <!-- Resumen por Régimen -->
    @if($por_regimen->count() > 0)
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Resumen por Régimen Aduanero</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Régimen</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total Trámites</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">P50 (Mediana)</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">P90</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Promedio</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Fuera de Umbral</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">% Fuera</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($por_regimen as $regimen)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $regimen['regimen'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900 font-medium">
                                {{ $regimen['total'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-blue-600 font-medium">
                                {{ number_format($regimen['p50_horas'], 2) }} h
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-orange-600 font-medium">
                                {{ number_format($regimen['p90_horas'], 2) }} h
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-purple-600 font-medium">
                                {{ number_format($regimen['promedio_horas'], 2) }} h
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-red-600 font-medium">
                                {{ $regimen['fuera_umbral'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $regimen['fuera_umbral_pct'] > 20 ? 'bg-red-100 text-red-800' : ($regimen['fuera_umbral_pct'] > 10 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                                    {{ number_format($regimen['fuera_umbral_pct'], 2) }}%
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Detalle de Trámites -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Detalle de Trámites Aprobados</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Trámite</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nave</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Régimen</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subpartida</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Entidad</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Inicio</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Fin</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tiempo Despacho</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($data as $tramite)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $tramite->tramite_ext_id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $tramite->vesselCall->vessel->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $tramite->regimen }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $tramite->subpartida ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $tramite->entidad->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $tramite->fecha_inicio->format('Y-m-d H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $tramite->fecha_fin ? $tramite->fecha_fin->format('Y-m-d H:i') : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($tramite->tiempo_despacho_h !== null)
                                    <span class="font-medium {{ $tramite->tiempo_despacho_h > ($kpis['umbral_horas'] ?? 24) ? 'text-red-600' : 'text-green-600' }}">
                                        {{ number_format($tramite->tiempo_despacho_h, 2) }} h
                                    </span>
                                @else
                                    <span class="text-gray-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    {{ str_replace('_', ' ', $tramite->estado) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-4 text-center text-sm text-gray-500">
                                No se encontraron trámites aprobados con los filtros aplicados
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($data->count() > 0)
        <div class="mt-4 text-sm text-gray-600">
            Total de trámites aprobados: {{ $data->count() }}
        </div>
    @endif

    <!-- Ayuda -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 class="font-semibold text-blue-900 mb-2">Ayuda</h3>
        <ul class="text-sm text-blue-800 space-y-1">
            <li><strong>Percentil 50 (P50):</strong> Mediana del tiempo de despacho. El 50% de los trámites se completan en este tiempo o menos</li>
            <li><strong>Percentil 90 (P90):</strong> El 90% de los trámites se completan en este tiempo o menos. Útil para identificar casos extremos</li>
            <li><strong>Tiempo de Despacho:</strong> Tiempo transcurrido desde el inicio del trámite hasta su aprobación final</li>
            <li><strong>Umbral:</strong> Tiempo máximo esperado para completar un trámite. Los trámites que exceden este umbral se marcan en rojo</li>
            <li><strong>Fuera de Umbral:</strong> Porcentaje de trámites que exceden el tiempo máximo configurado</li>
            <li><strong>Regímenes:</strong> IMPORTACION (entrada de mercancías), EXPORTACION (salida de mercancías), TRANSITO (paso por territorio)</li>
        </ul>
    </div>
</div>
@endsection
