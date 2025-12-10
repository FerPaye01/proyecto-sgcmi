@extends('layouts.app')

@section('title', 'Planificación de Nave')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Planificación de Nave</h1>
            <div class="flex space-x-2">
                @if(in_array($vesselCall->estado_llamada, ['PROGRAMADA', 'EN_TRANSITO']))
                    <a href="{{ route('vessel-planning.validate-arrival', $vesselCall) }}" 
                       class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium">
                        ✓ Validar Arribo
                    </a>
                @endif
                <a href="{{ route('vessel-calls.edit', $vesselCall) }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                    ✏️ Editar
                </a>
                <a href="{{ route('vessel-calls.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                    Volver
                </a>
            </div>
        </div>

        <!-- Vessel Call Basic Info -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Información Básica</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nave</label>
                    <p class="mt-1 text-lg text-gray-900">{{ $vesselCall->vessel->name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Viaje ID</label>
                    <p class="mt-1 text-lg text-gray-900">{{ $vesselCall->viaje_id ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Estado</label>
                    <p class="mt-1">
                        @if($vesselCall->estado_llamada === 'PROGRAMADA')
                            <span class="px-3 py-1 text-sm font-semibold rounded bg-blue-100 text-blue-800">Programada</span>
                        @elseif($vesselCall->estado_llamada === 'EN_TRANSITO')
                            <span class="px-3 py-1 text-sm font-semibold rounded bg-yellow-100 text-yellow-800">En Tránsito</span>
                        @elseif($vesselCall->estado_llamada === 'ATRACADA')
                            <span class="px-3 py-1 text-sm font-semibold rounded bg-green-100 text-green-800">Atracada</span>
                        @elseif($vesselCall->estado_llamada === 'OPERANDO')
                            <span class="px-3 py-1 text-sm font-semibold rounded bg-purple-100 text-purple-800">Operando</span>
                        @elseif($vesselCall->estado_llamada === 'ZARPO')
                            <span class="px-3 py-1 text-sm font-semibold rounded bg-gray-100 text-gray-800">Zarpó</span>
                        @elseif($vesselCall->estado_llamada === 'RECHAZADA')
                            <span class="px-3 py-1 text-sm font-semibold rounded bg-red-100 text-red-800">Rechazada</span>
                        @else
                            <span class="px-3 py-1 text-sm font-semibold rounded bg-gray-100 text-gray-800">{{ $vesselCall->estado_llamada }}</span>
                        @endif
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Muelle</label>
                    <p class="mt-1 text-lg text-gray-900">{{ $vesselCall->berth->name ?? 'No asignado' }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">ETA</label>
                    <p class="mt-1 text-lg text-gray-900">{{ $vesselCall->eta->format('d/m/Y H:i') }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">ETB</label>
                    <p class="mt-1 text-lg text-gray-900">{{ $vesselCall->etb ? $vesselCall->etb->format('d/m/Y H:i') : 'N/A' }}</p>
                </div>
            </div>
        </div>

        <!-- Ship Particulars -->
        @if($vesselCall->shipParticulars)
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Particulares de la Nave</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">LOA (Eslora)</label>
                    <p class="mt-1 text-lg text-gray-900">{{ $vesselCall->shipParticulars->loa }} m</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Manga</label>
                    <p class="mt-1 text-lg text-gray-900">{{ $vesselCall->shipParticulars->beam }} m</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Calado</label>
                    <p class="mt-1 text-lg text-gray-900">{{ $vesselCall->shipParticulars->draft }} m</p>
                </div>
                @if($vesselCall->shipParticulars->grt)
                <div>
                    <label class="block text-sm font-medium text-gray-700">GRT</label>
                    <p class="mt-1 text-lg text-gray-900">{{ number_format($vesselCall->shipParticulars->grt, 2) }}</p>
                </div>
                @endif
                @if($vesselCall->shipParticulars->nrt)
                <div>
                    <label class="block text-sm font-medium text-gray-700">NRT</label>
                    <p class="mt-1 text-lg text-gray-900">{{ number_format($vesselCall->shipParticulars->nrt, 2) }}</p>
                </div>
                @endif
                @if($vesselCall->shipParticulars->dwt)
                <div>
                    <label class="block text-sm font-medium text-gray-700">DWT</label>
                    <p class="mt-1 text-lg text-gray-900">{{ number_format($vesselCall->shipParticulars->dwt, 2) }}</p>
                </div>
                @endif
            </div>
            
            @if($vesselCall->shipParticulars->dangerous_cargo)
            <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                <p class="text-sm font-medium text-yellow-800">⚠️ Mercancías Peligrosas Declaradas</p>
            </div>
            @endif
        </div>
        @endif

        <!-- Loading Plans -->
        @if($vesselCall->loadingPlans->count() > 0)
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Plan de Carga/Descarga</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Secuencia</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Operación</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duración Est.</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cuadrilla Req.</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($vesselCall->loadingPlans as $plan)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $plan->sequence_order }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span class="px-2 py-1 text-xs font-semibold rounded
                                    @if($plan->operation_type === 'CARGA') bg-blue-100 text-blue-800
                                    @elseif($plan->operation_type === 'DESCARGA') bg-green-100 text-green-800
                                    @else bg-purple-100 text-purple-800
                                    @endif">
                                    {{ $plan->operation_type }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $plan->estimated_duration_h }} h</td>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $plan->crew_required ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span class="px-2 py-1 text-xs font-semibold rounded
                                    @if($plan->status === 'PLANIFICADO') bg-gray-100 text-gray-800
                                    @elseif($plan->status === 'EN_EJECUCION') bg-yellow-100 text-yellow-800
                                    @else bg-green-100 text-green-800
                                    @endif">
                                    {{ $plan->status }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Resource Allocations -->
        @if($vesselCall->resourceAllocations->count() > 0)
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Recursos Asignados</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Recurso</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Turno</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha Asignación</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($vesselCall->resourceAllocations as $allocation)
                        <tr>
                            <td class="px-4 py-3 text-sm">
                                <span class="px-2 py-1 text-xs font-semibold rounded
                                    @if($allocation->resource_type === 'EQUIPO') bg-blue-100 text-blue-800
                                    @elseif($allocation->resource_type === 'CUADRILLA') bg-green-100 text-green-800
                                    @else bg-purple-100 text-purple-800
                                    @endif">
                                    {{ $allocation->resource_type }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $allocation->resource_name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $allocation->quantity }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span class="px-2 py-1 text-xs font-semibold rounded
                                    @if($allocation->shift === 'MAÑANA') bg-yellow-100 text-yellow-800
                                    @elseif($allocation->shift === 'TARDE') bg-orange-100 text-orange-800
                                    @else bg-indigo-100 text-indigo-800
                                    @endif">
                                    {{ $allocation->shift }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $allocation->allocated_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <div class="flex justify-between items-center">
                <p class="text-sm text-yellow-800">⚠️ No se han asignado recursos a esta nave aún.</p>
                <button onclick="document.getElementById('resourceForm').classList.toggle('hidden')" 
                        class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                    + Asignar Recursos
                </button>
            </div>
        </div>

        <!-- Resource Allocation Form (Hidden by default) -->
        <div id="resourceForm" class="hidden bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Asignar Recursos</h3>
            <form action="{{ route('resource-planning.allocate') }}" method="POST">
                @csrf
                <input type="hidden" name="vessel_call_id" value="{{ $vesselCall->id }}">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tipo de Recurso *</label>
                        <select name="allocations[0][resource_type]" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="EQUIPO">Equipo</option>
                            <option value="CUADRILLA">Cuadrilla</option>
                            <option value="GAVIERO">Gaviero</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nombre del Recurso *</label>
                        <input type="text" name="allocations[0][resource_name]" required 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                               placeholder="Ej: Grúa 1, Cuadrilla A">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cantidad *</label>
                        <input type="number" name="allocations[0][quantity]" required min="1" value="1"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Turno *</label>
                        <select name="allocations[0][shift]" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="MAÑANA">Mañana</option>
                            <option value="TARDE">Tarde</option>
                            <option value="NOCHE">Noche</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fecha de Asignación *</label>
                        <input type="datetime-local" name="allocations[0][allocated_at]" required
                               value="{{ now()->format('Y-m-d\TH:i') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('resourceForm').classList.add('hidden')"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg">
                        Cancelar
                    </button>
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg">
                        Asignar Recurso
                    </button>
                </div>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection
