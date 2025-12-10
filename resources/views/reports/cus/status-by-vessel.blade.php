@extends('layouts.app')

@section('title', 'R7 - Estado de Trámites por Nave')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Reporte R7: Estado de Trámites por Nave</h1>
        <p class="text-gray-600 mt-2">Estado de trámites aduaneros agrupados por llamada de nave</p>
    </div>

    <!-- KPIs Section -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 mb-1">Completos Pre-Arribo</div>
            <div class="text-3xl font-bold text-green-600">{{ number_format($kpis['pct_completos_pre_arribo'], 2) }}%</div>
            <div class="text-xs text-gray-500 mt-1">Trámites finalizados antes del arribo</div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 mb-1">Lead Time Promedio</div>
            <div class="text-3xl font-bold text-blue-600">{{ number_format($kpis['lead_time_h'], 2) }}</div>
            <div class="text-xs text-gray-500 mt-1">horas desde inicio hasta aprobación</div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 mb-1">Trámites Aprobados</div>
            <div class="text-3xl font-bold text-green-600">{{ $kpis['aprobados'] }}</div>
            <div class="text-xs text-gray-500 mt-1">de {{ $kpis['total_tramites'] }} totales</div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 mb-1">Trámites Pendientes</div>
            <div class="text-3xl font-bold {{ $kpis['pendientes'] > 0 ? 'text-orange-600' : 'text-gray-600' }}">{{ $kpis['pendientes'] }}</div>
            <div class="text-xs text-gray-500 mt-1">{{ $kpis['rechazados'] }} rechazados</div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Filtros</h2>
        
        <form method="GET" action="{{ route('reports.r7') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
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
                <label for="vessel_id" class="block text-sm font-medium text-gray-700 mb-2">Nave</label>
                <select 
                    id="vessel_id" 
                    name="vessel_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">Todas las naves</option>
                    @foreach($vessels as $vessel)
                        <option value="{{ $vessel->id }}" {{ (isset($filters['vessel_id']) && $filters['vessel_id'] == $vessel->id) ? 'selected' : '' }}>
                            {{ $vessel->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="estado" class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                <select 
                    id="estado" 
                    name="estado"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">Todos los estados</option>
                    @foreach($estados as $key => $label)
                        <option value="{{ $key }}" {{ (isset($filters['estado']) && $filters['estado'] == $key) ? 'selected' : '' }}>
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

            <div class="md:col-span-5 flex gap-2">
                <button 
                    type="submit"
                    class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    Aplicar Filtros
                </button>
                <a 
                    href="{{ route('reports.r7') }}"
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
            <form method="POST" action="{{ route('export.report', ['report' => 'r7']) }}" class="inline">
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

            <form method="POST" action="{{ route('export.report', ['report' => 'r7']) }}" class="inline">
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

            <form method="POST" action="{{ route('export.report', ['report' => 'r7']) }}" class="inline">
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
            Los archivos se generarán con los filtros actualmente aplicados. Formato: reporte_r7_YYYY-MM-DD_HHMMSS.{ext}
        </p>
    </div>
    @endif

    <!-- Resumen por Nave -->
    @if($por_nave->count() > 0)
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Resumen por Nave</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nave</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Viaje</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ETA</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ATA</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aprobados</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Pendientes</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Rechazados</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Pre-Arribo</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($por_nave as $nave)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $nave['vessel_name'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $nave['viaje_id'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $nave['eta'] ? $nave['eta']->format('Y-m-d H:i') : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $nave['ata'] ? $nave['ata']->format('Y-m-d H:i') : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900 font-medium">
                                {{ $nave['total_tramites'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-green-600 font-medium">
                                {{ $nave['aprobados'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-orange-600 font-medium">
                                {{ $nave['pendientes'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-red-600 font-medium">
                                {{ $nave['rechazados'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                <span class="text-blue-600 font-medium">{{ $nave['completos_pre_arribo'] }}</span>
                                <span class="text-gray-500 text-xs">({{ number_format($nave['pct_completos'], 1) }}%)</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($nave['bloquea_operacion'])
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                        </svg>
                                        Bloqueado
                                    </span>
                                @else
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        OK
                                    </span>
                                @endif
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
            <h2 class="text-xl font-semibold text-gray-800">Detalle de Trámites</h2>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lead Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Bloquea</th>
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
                                @if($tramite->lead_time_h !== null)
                                    <span class="font-medium {{ $tramite->lead_time_h > 48 ? 'text-red-600' : 'text-green-600' }}">
                                        {{ number_format($tramite->lead_time_h, 2) }} h
                                    </span>
                                @else
                                    <span class="text-gray-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($tramite->estado === 'APROBADO') bg-green-100 text-green-800
                                    @elseif($tramite->estado === 'RECHAZADO') bg-red-100 text-red-800
                                    @elseif($tramite->estado === 'OBSERVADO') bg-yellow-100 text-yellow-800
                                    @elseif($tramite->estado === 'EN_REVISION') bg-blue-100 text-blue-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ str_replace('_', ' ', $tramite->estado) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($tramite->bloquea_operacion)
                                    <svg class="w-5 h-5 text-red-600 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd" />
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-green-600 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-4 text-center text-sm text-gray-500">
                                No se encontraron trámites con los filtros aplicados
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($data->count() > 0)
        <div class="mt-4 text-sm text-gray-600">
            Total de trámites: {{ $data->count() }}
        </div>
    @endif

    <!-- Ayuda -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 class="font-semibold text-blue-900 mb-2">Ayuda</h3>
        <ul class="text-sm text-blue-800 space-y-1">
            <li><strong>Completos Pre-Arribo:</strong> Porcentaje de trámites aprobados antes de la llegada real de la nave (ATA)</li>
            <li><strong>Lead Time:</strong> Tiempo promedio desde el inicio del trámite hasta su aprobación, medido en horas</li>
            <li><strong>Bloquea Operación:</strong> Indica si hay trámites pendientes (INICIADO, EN_REVISION, OBSERVADO) que impiden la operación</li>
            <li><strong>Estados:</strong> INICIADO → EN_REVISION → OBSERVADO (si hay problemas) → APROBADO o RECHAZADO</li>
        </ul>
    </div>
</div>
@endsection
