@extends('layouts.app')

@section('title', 'Detalle de Llamada de Nave')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Detalle de Llamada de Nave</h1>
            <div class="flex space-x-2">
                <a href="{{ route('vessel-calls.edit', $vesselCall) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                    Editar
                </a>
                <a href="{{ route('vessel-calls.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                    Volver
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <!-- Información de la Nave -->
            <div class="mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Información de la Nave</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nombre de la Nave</label>
                        <p class="mt-1 text-lg text-gray-900">{{ $vesselCall->vessel_name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Viaje ID</label>
                        <p class="mt-1 text-lg text-gray-900">{{ $vesselCall->viaje_id }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Muelle</label>
                        <p class="mt-1 text-lg text-gray-900">{{ $vesselCall->berth->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Estado</label>
                        <p class="mt-1">
                            @if($vesselCall->estado_llamada === 'PROGRAMADA')
                                <span class="px-3 py-1 text-sm font-semibold rounded bg-blue-100 text-blue-800">Programada</span>
                            @elseif($vesselCall->estado_llamada === 'EN_PUERTO')
                                <span class="px-3 py-1 text-sm font-semibold rounded bg-green-100 text-green-800">En Puerto</span>
                            @elseif($vesselCall->estado_llamada === 'COMPLETADA')
                                <span class="px-3 py-1 text-sm font-semibold rounded bg-gray-100 text-gray-800">Completada</span>
                            @else
                                <span class="px-3 py-1 text-sm font-semibold rounded bg-yellow-100 text-yellow-800">{{ $vesselCall->estado_llamada }}</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tiempos Programados -->
            <div class="mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Tiempos Programados</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">ETA (Estimated Time of Arrival)</label>
                        <p class="mt-1 text-lg text-gray-900">
                            {{ $vesselCall->eta ? \Carbon\Carbon::parse($vesselCall->eta)->format('d/m/Y H:i') : 'N/A' }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">ETB (Estimated Time of Berthing)</label>
                        <p class="mt-1 text-lg text-gray-900">
                            {{ $vesselCall->etb ? \Carbon\Carbon::parse($vesselCall->etb)->format('d/m/Y H:i') : 'N/A' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tiempos Reales -->
            <div class="mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Tiempos Reales</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">ATA (Actual Time of Arrival)</label>
                        <p class="mt-1 text-lg text-gray-900">
                            {{ $vesselCall->ata ? \Carbon\Carbon::parse($vesselCall->ata)->format('d/m/Y H:i') : 'N/A' }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">ATB (Actual Time of Berthing)</label>
                        <p class="mt-1 text-lg text-gray-900">
                            {{ $vesselCall->atb ? \Carbon\Carbon::parse($vesselCall->atb)->format('d/m/Y H:i') : 'N/A' }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">ATD (Actual Time of Departure)</label>
                        <p class="mt-1 text-lg text-gray-900">
                            {{ $vesselCall->atd ? \Carbon\Carbon::parse($vesselCall->atd)->format('d/m/Y H:i') : 'N/A' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- KPIs Calculados -->
            @if($vesselCall->ata && $vesselCall->eta)
            <div class="mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">KPIs</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700">Demora en Llegada</label>
                        <p class="mt-1 text-2xl font-bold text-blue-900">
                            @php
                                $delay = \Carbon\Carbon::parse($vesselCall->ata)->diffInHours(\Carbon\Carbon::parse($vesselCall->eta), false);
                            @endphp
                            {{ number_format($delay, 1) }} horas
                        </p>
                    </div>
                    @if($vesselCall->atb && $vesselCall->atd)
                    <div class="bg-green-50 p-4 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700">Tiempo en Puerto</label>
                        <p class="mt-1 text-2xl font-bold text-green-900">
                            @php
                                $portTime = \Carbon\Carbon::parse($vesselCall->atd)->diffInHours(\Carbon\Carbon::parse($vesselCall->atb));
                            @endphp
                            {{ number_format($portTime, 1) }} horas
                        </p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Metadatos -->
            <div class="border-t pt-4">
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Información del Registro</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                    <div>
                        <span class="font-medium">Creado:</span> {{ $vesselCall->created_at->format('d/m/Y H:i') }}
                    </div>
                    <div>
                        <span class="font-medium">Actualizado:</span> {{ $vesselCall->updated_at->format('d/m/Y H:i') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
