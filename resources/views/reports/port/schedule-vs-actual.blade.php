@extends('layouts.app')

@section('title', 'R1 - Programación vs Ejecución')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Reporte R1: Programación vs Ejecución</h1>
        <p class="text-gray-600 mt-2">Comparativa entre tiempos programados (ETA/ETB) y reales (ATA/ATB/ATD)</p>
    </div>

    <!-- KPIs Section -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 mb-1">Puntualidad de Arribo</div>
            <div class="text-3xl font-bold text-blue-600">{{ number_format($kpis['puntualidad_arribo'], 2) }}%</div>
            <div class="text-xs text-gray-500 mt-1">±1 hora de tolerancia</div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 mb-1">Demora ETA-ATA</div>
            <div class="text-3xl font-bold text-orange-600">{{ number_format($kpis['demora_eta_ata_min'], 2) }}</div>
            <div class="text-xs text-gray-500 mt-1">minutos promedio</div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 mb-1">Demora ETB-ATB</div>
            <div class="text-3xl font-bold text-orange-600">{{ number_format($kpis['demora_etb_atb_min'], 2) }}</div>
            <div class="text-xs text-gray-500 mt-1">minutos promedio</div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 mb-1">Cumplimiento Ventana</div>
            <div class="text-3xl font-bold text-green-600">{{ number_format($kpis['cumplimiento_ventana'], 2) }}%</div>
            <div class="text-xs text-gray-500 mt-1">dentro de ventana</div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="bg-white rounded-lg shadow p-6 mb-6" x-data="reportFilters()">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Filtros</h2>
        
        <form method="GET" action="{{ route('reports.r1') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                <label for="berth_id" class="block text-sm font-medium text-gray-700 mb-2">Muelle</label>
                <select 
                    id="berth_id" 
                    name="berth_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">Todos los muelles</option>
                    @foreach($berths as $berth)
                        <option value="{{ $berth->id }}" {{ (isset($filters['berth_id']) && $filters['berth_id'] == $berth->id) ? 'selected' : '' }}>
                            {{ $berth->name }}
                        </option>
                    @endforeach
                </select>
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

            <div class="md:col-span-4 flex gap-2">
                <button 
                    type="submit"
                    class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    Aplicar Filtros
                </button>
                <a 
                    href="{{ route('reports.r1') }}"
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
            <form method="POST" action="{{ route('export.report', ['report' => 'r1']) }}" class="inline">
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

            <form method="POST" action="{{ route('export.report', ['report' => 'r1']) }}" class="inline">
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

            <form method="POST" action="{{ route('export.report', ['report' => 'r1']) }}" class="inline">
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
            Los archivos se generarán con los filtros actualmente aplicados. Formato: reporte_r1_YYYY-MM-DD_HHMMSS.{ext}
        </p>
    </div>
    @endif

    <!-- Data Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nave</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Viaje</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Muelle</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ETA</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ATA</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ETB</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ATB</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ATD</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Demora ETA-ATA</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Demora ETB-ATB</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($data as $vesselCall)
                        @php
                            $demoraEtaAta = null;
                            if ($vesselCall->eta && $vesselCall->ata) {
                                $demoraEtaAta = round(($vesselCall->ata->timestamp - $vesselCall->eta->timestamp) / 60, 2);
                            }
                            
                            $demoraEtbAtb = null;
                            if ($vesselCall->etb && $vesselCall->atb) {
                                $demoraEtbAtb = round(($vesselCall->atb->timestamp - $vesselCall->etb->timestamp) / 60, 2);
                            }
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $vesselCall->vessel->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $vesselCall->viaje_id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $vesselCall->berth->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $vesselCall->eta ? $vesselCall->eta->format('Y-m-d H:i') : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $vesselCall->ata ? $vesselCall->ata->format('Y-m-d H:i') : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $vesselCall->etb ? $vesselCall->etb->format('Y-m-d H:i') : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $vesselCall->atb ? $vesselCall->atb->format('Y-m-d H:i') : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $vesselCall->atd ? $vesselCall->atd->format('Y-m-d H:i') : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($demoraEtaAta !== null)
                                    <span class="@if($demoraEtaAta > 60) text-red-600 @elseif($demoraEtaAta > 0) text-orange-600 @else text-green-600 @endif font-medium">
                                        {{ number_format($demoraEtaAta, 2) }} min
                                    </span>
                                @else
                                    <span class="text-gray-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($demoraEtbAtb !== null)
                                    <span class="@if($demoraEtbAtb > 60) text-red-600 @elseif($demoraEtbAtb > 0) text-orange-600 @else text-green-600 @endif font-medium">
                                        {{ number_format($demoraEtbAtb, 2) }} min
                                    </span>
                                @else
                                    <span class="text-gray-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($vesselCall->estado_llamada === 'FINALIZADA') bg-green-100 text-green-800
                                    @elseif($vesselCall->estado_llamada === 'OPERANDO') bg-blue-100 text-blue-800
                                    @elseif($vesselCall->estado_llamada === 'ATRACADA') bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ $vesselCall->estado_llamada }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-6 py-4 text-center text-sm text-gray-500">
                                No se encontraron registros con los filtros aplicados
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($data->count() > 0)
        <div class="mt-4 text-sm text-gray-600">
            Total de registros: {{ $data->count() }}
        </div>
    @endif
</div>

<script>
function reportFilters() {
    return {
        // Alpine.js component for future enhancements
    };
}
</script>
@endsection
