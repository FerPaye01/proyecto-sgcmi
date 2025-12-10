@extends('layouts.app')

@section('title', 'Eventos de Gate')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Eventos de Gate</h1>
        
        @can('GATE_EVENT_WRITE')
            <button 
                onclick="showCreateModal()"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-colors">
                Registrar Evento
            </button>
        @endcan
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6" x-data="{ showFilters: false }">
        <button @click="showFilters = !showFilters" class="text-blue-600 hover:text-blue-800 font-semibold mb-4">
            <span x-show="!showFilters">▶ Mostrar Filtros</span>
            <span x-show="showFilters">▼ Ocultar Filtros</span>
        </button>

        <form method="GET" action="{{ route('gate-events.index') }}" x-show="showFilters" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Desde</label>
                <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Hasta</label>
                <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Gate</label>
                <select name="gate_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Todos los Gates</option>
                    @foreach(\App\Models\Gate::where('activo', true)->orderBy('name')->get() as $gate)
                        <option value="{{ $gate->id }}" {{ request('gate_id') == $gate->id ? 'selected' : '' }}>
                            {{ $gate->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Acción</label>
                <select name="action" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Todas</option>
                    <option value="ENTRADA" {{ request('action') === 'ENTRADA' ? 'selected' : '' }}>ENTRADA</option>
                    <option value="SALIDA" {{ request('action') === 'SALIDA' ? 'selected' : '' }}>SALIDA</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Placa de Camión</label>
                <input type="text" 
                       name="truck_placa" 
                       value="{{ request('truck_placa') }}" 
                       placeholder="Ej: ABC-123"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ID de Cita</label>
                <input type="number" 
                       name="cita_id" 
                       value="{{ request('cita_id') }}" 
                       placeholder="ID de cita"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div class="md:col-span-3 flex gap-2">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors">
                    Aplicar Filtros
                </button>
                <a href="{{ route('gate-events.index') }}" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-400 transition-colors">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    <!-- Gate Events Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Camión</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acción</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha/Hora</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cita</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Empresa</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($gateEvents as $event)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $event->id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span class="font-medium">{{ $event->gate->code ?? 'N/A' }}</span>
                                <span class="text-gray-500 text-xs block">{{ $event->gate->name ?? '' }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $event->truck->placa ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $event->action === 'ENTRADA' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $event->action }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $event->event_ts?->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($event->cita_id)
                                    <a href="{{ route('appointments.index', ['id' => $event->cita_id]) }}" 
                                       class="text-blue-600 hover:text-blue-800 hover:underline">
                                        #{{ $event->cita_id }}
                                    </a>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $event->truck->company->name ?? 'N/A' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <p class="mt-2">No se encontraron eventos de gate</p>
                                <p class="text-sm text-gray-400 mt-1">Los eventos de entrada y salida de camiones aparecerán aquí</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($gateEvents->hasPages())
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                {{ $gateEvents->links() }}
            </div>
        @endif
    </div>

    <!-- Help Section -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 class="font-semibold text-blue-900 mb-2">Información</h3>
        <ul class="text-sm text-blue-800 space-y-1">
            <li><strong>Eventos de Gate:</strong> Registros de entrada y salida de camiones en los gates del puerto</li>
            <li><strong>ENTRADA:</strong> Momento en que el camión ingresa al recinto portuario</li>
            <li><strong>SALIDA:</strong> Momento en que el camión sale del recinto portuario</li>
            <li><strong>Cita:</strong> Si el evento está asociado a una cita programada, se muestra el ID</li>
            <li><strong>Tiempo de Ciclo:</strong> Se calcula como la diferencia entre la entrada y salida del mismo camión</li>
        </ul>
    </div>

    <!-- Statistics Summary -->
    @if($gateEvents->count() > 0)
        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm text-gray-500">Total Eventos</div>
                <div class="text-2xl font-bold text-gray-900">{{ $gateEvents->total() }}</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm text-gray-500">Entradas</div>
                <div class="text-2xl font-bold text-green-600">
                    {{ $gateEvents->where('action', 'ENTRADA')->count() }}
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm text-gray-500">Salidas</div>
                <div class="text-2xl font-bold text-red-600">
                    {{ $gateEvents->where('action', 'SALIDA')->count() }}
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Create Gate Event Modal -->
@can('GATE_EVENT_WRITE')
<div id="createModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" x-data="{ show: false }">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Registrar Evento de Gate</h3>
            <button onclick="hideCreateModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form action="{{ route('gate-events.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Gate <span class="text-red-500">*</span></label>
                <select name="gate_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                    <option value="">Seleccione un gate</option>
                    @foreach(\App\Models\Gate::where('activo', true)->orderBy('name')->get() as $gate)
                        <option value="{{ $gate->id }}">{{ $gate->name }} ({{ $gate->code }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Camión <span class="text-red-500">*</span></label>
                <select name="truck_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                    <option value="">Seleccione un camión</option>
                    @foreach(\App\Models\Truck::where('activo', true)->with('company')->orderBy('placa')->get() as $truck)
                        <option value="{{ $truck->id }}">{{ $truck->placa }} - {{ $truck->company->name ?? 'N/A' }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Acción <span class="text-red-500">*</span></label>
                <select name="action" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                    <option value="">Seleccione una acción</option>
                    <option value="ENTRADA">ENTRADA</option>
                    <option value="SALIDA">SALIDA</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha y Hora <span class="text-red-500">*</span></label>
                <input type="datetime-local" 
                       name="event_ts" 
                       required
                       value="{{ now()->format('Y-m-d\TH:i') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ID de Cita (Opcional)</label>
                <input type="number" 
                       name="cita_id" 
                       placeholder="Dejar vacío si no está asociado a una cita"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t">
                <button type="button" 
                        onclick="hideCreateModal()"
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Registrar Evento
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showCreateModal() {
    document.getElementById('createModal').classList.remove('hidden');
}

function hideCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
}
</script>
@endcan

@endsection
