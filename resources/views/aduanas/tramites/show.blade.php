@extends('layouts.app')

@section('title', 'Detalle del Trámite')

@section('content')
<div class="max-w-6xl mx-auto">
    <!-- Header Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Detalle del Trámite</h2>
                <p class="text-gray-600">ID: {{ $tramite->tramite_ext_id }}</p>
            </div>
            <div class="flex space-x-2">
                @can('update', $tramite)
                    <a href="{{ route('tramites.edit', $tramite) }}" 
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition-colors">
                        Editar
                    </a>
                @endcan
                <a href="{{ route('tramites.index') }}" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded transition-colors">
                    Volver
                </a>
            </div>
        </div>

        <!-- Status Badge -->
        <div class="mb-4">
            @php
                $statusColors = [
                    'INICIADO' => 'bg-blue-100 text-blue-800',
                    'EN_REVISION' => 'bg-yellow-100 text-yellow-800',
                    'OBSERVADO' => 'bg-orange-100 text-orange-800',
                    'APROBADO' => 'bg-green-100 text-green-800',
                    'RECHAZADO' => 'bg-red-100 text-red-800',
                ];
                $statusColor = $statusColors[$tramite->estado] ?? 'bg-gray-100 text-gray-800';
            @endphp
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColor }}">
                {{ $tramite->estado }}
            </span>
        </div>

        <!-- Tramite Information Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Vessel Call -->
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Llamada de Nave</label>
                <p class="text-gray-900">
                    @if($tramite->vesselCall)
                        {{ $tramite->vesselCall->vessel->name ?? 'N/A' }} - {{ $tramite->vesselCall->viaje_id }}
                    @else
                        N/A
                    @endif
                </p>
            </div>

            <!-- Regimen -->
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Régimen</label>
                <p class="text-gray-900">{{ $tramite->regimen }}</p>
            </div>

            <!-- Subpartida -->
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Subpartida Arancelaria</label>
                <p class="text-gray-900">{{ $tramite->subpartida ?? 'N/A' }}</p>
            </div>

            <!-- Entidad -->
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Entidad Aduanera</label>
                <p class="text-gray-900">
                    @if($tramite->entidad)
                        {{ $tramite->entidad->name }} ({{ $tramite->entidad->code }})
                    @else
                        N/A
                    @endif
                </p>
            </div>

            <!-- Fecha Inicio -->
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Fecha de Inicio</label>
                <p class="text-gray-900">{{ $tramite->fecha_inicio?->format('d/m/Y H:i') ?? 'N/A' }}</p>
            </div>

            <!-- Fecha Fin -->
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Fecha de Fin</label>
                <p class="text-gray-900">{{ $tramite->fecha_fin?->format('d/m/Y H:i') ?? 'En proceso' }}</p>
            </div>

            <!-- Lead Time -->
            @if($tramite->fecha_fin)
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Tiempo Total (Lead Time)</label>
                <p class="text-gray-900">
                    @php
                        $hours = $tramite->fecha_inicio->diffInHours($tramite->fecha_fin);
                        $days = floor($hours / 24);
                        $remainingHours = $hours % 24;
                    @endphp
                    @if($days > 0)
                        {{ $days }} día{{ $days > 1 ? 's' : '' }} {{ $remainingHours }}h
                    @else
                        {{ $remainingHours }} hora{{ $remainingHours != 1 ? 's' : '' }}
                    @endif
                </p>
            </div>
            @endif
        </div>
    </div>

    <!-- Add Event Form -->
    @can('update', $tramite)
    <div class="bg-white rounded-lg shadow-md p-6 mb-6" x-data="eventForm()">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Registrar Nuevo Evento</h3>
        
        <form action="{{ route('tramites.addEvent', $tramite) }}" 
              method="POST"
              @submit="validateForm($event)">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Estado -->
                <div>
                    <label for="estado" class="block text-sm font-medium text-gray-700 mb-2">
                        Nuevo Estado <span class="text-red-500">*</span>
                    </label>
                    <select name="estado" 
                            id="estado" 
                            required
                            x-model="estado"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Seleccione un estado</option>
                        <option value="INICIADO">Iniciado</option>
                        <option value="EN_REVISION">En Revisión</option>
                        <option value="OBSERVADO">Observado</option>
                        <option value="APROBADO">Aprobado</option>
                        <option value="RECHAZADO">Rechazado</option>
                    </select>
                </div>

                <!-- Motivo -->
                <div>
                    <label for="motivo" class="block text-sm font-medium text-gray-700 mb-2">
                        Motivo / Observaciones
                    </label>
                    <input type="text" 
                           name="motivo" 
                           id="motivo" 
                           maxlength="1000"
                           x-model="motivo"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Descripción del cambio de estado">
                </div>
            </div>

            <!-- Validation Messages -->
            <div x-show="validationError" 
                 x-transition
                 class="mt-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <p x-text="validationError"></p>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end mt-4">
                <button type="submit" 
                        class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                    Registrar Evento
                </button>
            </div>
        </form>
    </div>
    @endcan

    <!-- Timeline Section -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-6">Línea de Tiempo de Eventos</h3>

        @if($tramite->events->isEmpty())
            <div class="text-center py-8 text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-lg">No hay eventos registrados para este trámite</p>
            </div>
        @else
            <!-- Timeline Container -->
            <div class="relative">
                <!-- Vertical Line -->
                <div class="absolute left-8 top-0 bottom-0 w-0.5 bg-gray-300"></div>

                <!-- Timeline Events -->
                <div class="space-y-6">
                    @foreach($tramite->events->sortByDesc('event_ts') as $event)
                    <div class="relative flex items-start">
                        <!-- Timeline Dot -->
                        <div class="absolute left-8 -ml-2 mt-1.5">
                            @php
                                $dotColors = [
                                    'INICIADO' => 'bg-blue-500',
                                    'EN_REVISION' => 'bg-yellow-500',
                                    'OBSERVADO' => 'bg-orange-500',
                                    'APROBADO' => 'bg-green-500',
                                    'RECHAZADO' => 'bg-red-500',
                                ];
                                $dotColor = $dotColors[$event->estado] ?? 'bg-gray-500';
                            @endphp
                            <div class="h-4 w-4 rounded-full {{ $dotColor }} border-4 border-white shadow"></div>
                        </div>

                        <!-- Event Content -->
                        <div class="ml-20 flex-1">
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 hover:shadow-md transition-shadow">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <h4 class="font-semibold text-gray-900">{{ $event->estado }}</h4>
                                        <p class="text-sm text-gray-600">
                                            {{ $event->event_ts->format('d/m/Y H:i:s') }}
                                            <span class="text-gray-400">
                                                ({{ $event->event_ts->diffForHumans() }})
                                            </span>
                                        </p>
                                    </div>
                                    @php
                                        $badgeColors = [
                                            'INICIADO' => 'bg-blue-100 text-blue-800',
                                            'EN_REVISION' => 'bg-yellow-100 text-yellow-800',
                                            'OBSERVADO' => 'bg-orange-100 text-orange-800',
                                            'APROBADO' => 'bg-green-100 text-green-800',
                                            'RECHAZADO' => 'bg-red-100 text-red-800',
                                        ];
                                        $badgeColor = $badgeColors[$event->estado] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeColor }}">
                                        {{ $event->estado }}
                                    </span>
                                </div>

                                @if($event->motivo)
                                <div class="mt-2 pt-2 border-t border-gray-200">
                                    <p class="text-sm text-gray-700">
                                        <span class="font-medium">Motivo:</span> {{ $event->motivo }}
                                    </p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Timeline Summary -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <p class="text-sm text-blue-600 font-medium">Total de Eventos</p>
                        <p class="text-2xl font-bold text-blue-900">{{ $tramite->events->count() }}</p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4">
                        <p class="text-sm text-green-600 font-medium">Primer Evento</p>
                        <p class="text-lg font-semibold text-green-900">
                            {{ $tramite->events->sortBy('event_ts')->first()->event_ts->format('d/m/Y H:i') }}
                        </p>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-4">
                        <p class="text-sm text-purple-600 font-medium">Último Evento</p>
                        <p class="text-lg font-semibold text-purple-900">
                            {{ $tramite->events->sortByDesc('event_ts')->first()->event_ts->format('d/m/Y H:i') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Success Message -->
    @if(session('success'))
    <div x-data="{ show: true }" 
         x-show="show" 
         x-init="setTimeout(() => show = false, 5000)"
         x-transition
         class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg">
        {{ session('success') }}
    </div>
    @endif
</div>

<script>
function eventForm() {
    return {
        estado: '',
        motivo: '',
        validationError: '',

        validateForm(event) {
            this.validationError = '';

            // Validate estado is selected
            if (!this.estado) {
                this.validationError = 'Debe seleccionar un estado';
                event.preventDefault();
                return false;
            }

            return true;
        }
    };
}
</script>
@endsection
