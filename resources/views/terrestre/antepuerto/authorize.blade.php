@extends('layouts.app')

@section('title', 'Autorizar Entrada al Terminal')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Autorizar Entrada al Terminal</h1>
        <p class="mt-2 text-gray-600">Revisar y autorizar o rechazar entrada desde antepuerto</p>
    </div>

    <!-- Queue Entry Details -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Detalles del Vehículo</h2>
        </div>
        <div class="p-6">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Placa</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-semibold">{{ $queueEntry->truck->placa ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Empresa</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $queueEntry->truck->company->nombre ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Zona</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold">
                            {{ $queueEntry->zone }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Cita</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        @if($queueEntry->appointment)
                            <span class="text-green-600">✓ Cita #{{ $queueEntry->appointment_id }}</span>
                            <br>
                            <span class="text-xs text-gray-500">
                                Programada: {{ $queueEntry->appointment->hora_programada->format('d/m/Y H:i') }}
                            </span>
                        @else
                            <span class="text-gray-400">Sin cita programada</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Hora de Entrada</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $queueEntry->entry_time->format('d/m/Y H:i:s') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Tiempo en Espera</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <span class="font-semibold {{ $queueEntry->getWaitingTimeMinutes() > 60 ? 'text-red-600' : 'text-gray-900' }}">
                            {{ $queueEntry->getWaitingTimeMinutes() }} minutos
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Estado Actual</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold">
                            {{ $queueEntry->status }}
                        </span>
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Authorization Form -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Decisión de Autorización</h2>
        </div>
        <div class="p-6">
            <form action="{{ route('antepuerto.authorize-entry') }}" method="POST" class="space-y-6">
                @csrf
                <input type="hidden" name="queue_id" value="{{ $queueEntry->id }}">

                <!-- Action Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Acción</label>
                    <div class="space-y-3">
                        <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                            <input type="radio" name="action" value="AUTORIZAR" required
                                   class="h-4 w-4 text-green-600 focus:ring-green-500">
                            <div class="ml-3">
                                <span class="block text-sm font-medium text-gray-900">
                                    ✓ Autorizar Entrada al Terminal
                                </span>
                                <span class="block text-xs text-gray-500">
                                    El vehículo podrá ingresar al terminal
                                </span>
                            </div>
                        </label>

                        <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                            <input type="radio" name="action" value="RECHAZAR" required
                                   class="h-4 w-4 text-red-600 focus:ring-red-500">
                            <div class="ml-3">
                                <span class="block text-sm font-medium text-gray-900">
                                    ✗ Rechazar Entrada
                                </span>
                                <span class="block text-xs text-gray-500">
                                    El vehículo no podrá ingresar al terminal
                                </span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Reason -->
                <div>
                    <label for="reason" class="block text-sm font-medium text-gray-700">
                        Motivo / Observaciones (Opcional)
                    </label>
                    <textarea name="reason" id="reason" rows="3"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                              placeholder="Ingrese el motivo de la decisión..."></textarea>
                    <p class="mt-1 text-xs text-gray-500">
                        Especialmente importante si se rechaza la entrada
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-3">
                    <a href="{{ route('antepuerto.queue') }}"
                       class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-md transition-colors">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
                        Confirmar Decisión
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Back Link -->
    <div class="mt-6">
        <a href="{{ route('antepuerto.queue') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
            ← Volver a la cola de antepuerto
        </a>
    </div>
</div>
@endsection
