@extends('layouts.app')

@section('title', 'Configuración de Umbrales')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Configuración de Umbrales</h1>
        <p class="text-gray-600 mt-2">Gestiona los umbrales de alertas tempranas y SLAs del sistema</p>
    </div>

    <!-- Alert Thresholds Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 pb-4 border-b-2 border-blue-500">
            Umbrales de Alertas Tempranas
        </h2>

        <form action="{{ route('admin.settings.thresholds.update') }}" method="POST" class="space-y-6">
            @csrf
            @method('PATCH')

            <!-- Berth Utilization Alert -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <label for="alert_berth_utilization" class="block text-sm font-semibold text-gray-700 mb-2">
                    Umbral de Utilización de Muelles (%)
                </label>
                <div class="flex items-center space-x-4">
                    <input 
                        type="number" 
                        id="alert_berth_utilization" 
                        name="alert_berth_utilization" 
                        min="0" 
                        max="100" 
                        step="1"
                        value="{{ $thresholds['alert_berth_utilization'] ?? 85 }}"
                        class="w-24 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                    >
                    <span class="text-gray-600">%</span>
                    <p class="text-sm text-gray-500 ml-4">
                        Genera alerta cuando la utilización de un muelle supera este porcentaje
                    </p>
                </div>
                @error('alert_berth_utilization')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <!-- Truck Waiting Time Alert -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <label for="alert_truck_waiting_time" class="block text-sm font-semibold text-gray-700 mb-2">
                    Umbral de Tiempo de Espera de Camiones (horas)
                </label>
                <div class="flex items-center space-x-4">
                    <input 
                        type="number" 
                        id="alert_truck_waiting_time" 
                        name="alert_truck_waiting_time" 
                        min="0" 
                        max="24" 
                        step="0.5"
                        value="{{ $thresholds['alert_truck_waiting_time'] ?? 4 }}"
                        class="w-24 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                    >
                    <span class="text-gray-600">horas</span>
                    <p class="text-sm text-gray-500 ml-4">
                        Genera alerta cuando el tiempo promedio de espera supera este valor
                    </p>
                </div>
                @error('alert_truck_waiting_time')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <!-- SLA Thresholds Section -->
            <div class="mt-8 pt-8 border-t-2 border-gray-200">
                <h3 class="text-xl font-bold text-gray-900 mb-6">Umbrales de SLAs</h3>

                <!-- Turnaround SLA -->
                <div class="bg-gray-50 p-4 rounded-lg mb-4">
                    <label for="sla_turnaround" class="block text-sm font-semibold text-gray-700 mb-2">
                        SLA Turnaround (horas)
                    </label>
                    <div class="flex items-center space-x-4">
                        <input 
                            type="number" 
                            id="sla_turnaround" 
                            name="sla_turnaround" 
                            min="0" 
                            max="168" 
                            step="1"
                            value="{{ $thresholds['sla_turnaround'] ?? 48 }}"
                            class="w-24 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                        <span class="text-gray-600">horas</span>
                        <p class="text-sm text-gray-500 ml-4">
                            Tiempo máximo permitido desde arribo hasta salida de una nave
                        </p>
                    </div>
                    @error('sla_turnaround')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Truck Waiting Time SLA -->
                <div class="bg-gray-50 p-4 rounded-lg mb-4">
                    <label for="sla_truck_waiting_time" class="block text-sm font-semibold text-gray-700 mb-2">
                        SLA Tiempo de Espera de Camiones (horas)
                    </label>
                    <div class="flex items-center space-x-4">
                        <input 
                            type="number" 
                            id="sla_truck_waiting_time" 
                            name="sla_truck_waiting_time" 
                            min="0" 
                            max="24" 
                            step="0.5"
                            value="{{ $thresholds['sla_truck_waiting_time'] ?? 2 }}"
                            class="w-24 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                        <span class="text-gray-600">horas</span>
                        <p class="text-sm text-gray-500 ml-4">
                            Tiempo máximo permitido de espera para camiones en gates
                        </p>
                    </div>
                    @error('sla_truck_waiting_time')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Customs Dispatch SLA -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <label for="sla_customs_dispatch" class="block text-sm font-semibold text-gray-700 mb-2">
                        SLA Despacho Aduanero (horas)
                    </label>
                    <div class="flex items-center space-x-4">
                        <input 
                            type="number" 
                            id="sla_customs_dispatch" 
                            name="sla_customs_dispatch" 
                            min="0" 
                            max="168" 
                            step="1"
                            value="{{ $thresholds['sla_customs_dispatch'] ?? 24 }}"
                            class="w-24 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                        <span class="text-gray-600">horas</span>
                        <p class="text-sm text-gray-500 ml-4">
                            Tiempo máximo permitido para completar trámites aduaneros
                        </p>
                    </div>
                    @error('sla_customs_dispatch')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                <a href="{{ route('dashboard') }}" class="text-gray-600 hover:text-gray-900 font-medium">
                    ← Volver
                </a>
                <div class="space-x-4">
                    <button 
                        type="reset" 
                        class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 font-medium transition-colors"
                    >
                        Limpiar
                    </button>
                    <button 
                        type="submit" 
                        class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium transition-colors"
                    >
                        Guardar Cambios
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Information Box -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 class="font-semibold text-blue-900 mb-2">ℹ️ Información</h3>
        <ul class="text-sm text-blue-800 space-y-1">
            <li>• Los cambios en estos umbrales afectarán la generación de alertas tempranas en tiempo real</li>
            <li>• Los SLAs se utilizan para calcular el cumplimiento de acuerdos de nivel de servicio</li>
            <li>• Todos los cambios se registran en el log de auditoría</li>
            <li>• Solo usuarios con rol ADMIN pueden modificar estos valores</li>
        </ul>
    </div>
</div>
@endsection
