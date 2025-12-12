@extends('layouts.app')

@section('title', 'Demo: Control de Acceso Mejorado')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-lg shadow-lg p-6 mb-8 text-white">
        <h1 class="text-3xl font-bold mb-2">🚀 Demo: Sistema de Control de Acceso Mejorado</h1>
        <p class="text-blue-100">Nuevas funcionalidades implementadas en la Tarea 10 - Sprint 3</p>
    </div>

    <!-- Comparison Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- ANTES -->
        <div class="bg-gray-50 border-2 border-gray-300 rounded-lg p-6">
            <div class="flex items-center mb-4">
                <span class="text-3xl mr-3">📋</span>
                <h2 class="text-xl font-bold text-gray-700">ANTES (Tareas 1-9)</h2>
            </div>
            <div class="space-y-3">
                <div class="flex items-start">
                    <span class="text-green-500 mr-2">✓</span>
                    <span class="text-sm">Sistema básico de citas (Appointment)</span>
                </div>
                <div class="flex items-start">
                    <span class="text-green-500 mr-2">✓</span>
                    <span class="text-sm">Registro de eventos de gate (GateEvent)</span>
                </div>
                <div class="flex items-start">
                    <span class="text-green-500 mr-2">✓</span>
                    <span class="text-sm">Gestión básica de camiones (Truck)</span>
                </div>
                <div class="flex items-start">
                    <span class="text-red-500 mr-2">✗</span>
                    <span class="text-sm text-gray-500">Sin pases digitales con QR</span>
                </div>
                <div class="flex items-start">
                    <span class="text-red-500 mr-2">✗</span>
                    <span class="text-sm text-gray-500">Sin gestión de antepuerto/ZOE</span>
                </div>
                <div class="flex items-start">
                    <span class="text-red-500 mr-2">✗</span>
                    <span class="text-sm text-gray-500">Sin permisos de acceso vinculados a carga</span>
                </div>
            </div>
        </div>

        <!-- AHORA -->
        <div class="bg-gradient-to-br from-green-50 to-emerald-50 border-2 border-green-400 rounded-lg p-6">
            <div class="flex items-center mb-4">
                <span class="text-3xl mr-3">🎯</span>
                <h2 class="text-xl font-bold text-green-700">AHORA (Tarea 10 - NUEVO)</h2>
            </div>
            <div class="space-y-3">
                <div class="flex items-start">
                    <span class="text-green-600 mr-2 font-bold">✓</span>
                    <span class="text-sm font-semibold">Pases Digitales con QR (DigitalPass)</span>
                </div>
                <div class="flex items-start">
                    <span class="text-green-600 mr-2 font-bold">✓</span>
                    <span class="text-sm font-semibold">Permisos de Acceso (AccessPermit)</span>
                </div>
                <div class="flex items-start">
                    <span class="text-green-600 mr-2 font-bold">✓</span>
                    <span class="text-sm font-semibold">Cola de Antepuerto/ZOE (AntepuertoQueue)</span>
                </div>
                <div class="flex items-start">
                    <span class="text-green-600 mr-2 font-bold">✓</span>
                    <span class="text-sm font-semibold">Vinculación con carga (CargoItem)</span>
                </div>
                <div class="flex items-start">
                    <span class="text-green-600 mr-2 font-bold">✓</span>
                    <span class="text-sm font-semibold">Validación de permisos múltiples</span>
                </div>
                <div class="flex items-start">
                    <span class="text-green-600 mr-2 font-bold">✓</span>
                    <span class="text-sm font-semibold">Gestión de tiempos de espera</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Interactive Demo Sections -->
    <div class="space-y-6">
        <!-- 1. Digital Pass Demo -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden" x-data="{ 
            passes: @json($digitalPasses),
            selectedPass: null,
            showQr: false
        }">
            <div class="bg-indigo-600 text-white px-6 py-4">
                <h3 class="text-lg font-bold flex items-center">
                    <span class="text-2xl mr-3">🎫</span>
                    1. Pases Digitales con QR (NUEVO)
                </h3>
                <p class="text-sm text-indigo-100 mt-1">Generación automática de códigos QR únicos para control de acceso</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                        <div class="text-2xl font-bold text-blue-600" x-text="passes.filter(p => p.status === 'ACTIVO').length"></div>
                        <div class="text-sm text-gray-600">Pases Activos</div>
                    </div>
                    <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                        <div class="text-2xl font-bold text-yellow-600" x-text="passes.filter(p => p.status === 'VENCIDO').length"></div>
                        <div class="text-sm text-gray-600">Pases Vencidos</div>
                    </div>
                    <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                        <div class="text-2xl font-bold text-red-600" x-text="passes.filter(p => p.status === 'REVOCADO').length"></div>
                        <div class="text-sm text-gray-600">Pases Revocados</div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Titular</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vehículo</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vigencia</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">QR</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="pass in passes" :key="pass.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-mono font-bold text-indigo-600" x-text="pass.pass_code"></td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="px-2 py-1 rounded text-xs" 
                                              :class="pass.pass_type === 'VEHICULAR' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'"
                                              x-text="pass.pass_type"></span>
                                    </td>
                                    <td class="px-4 py-3 text-sm" x-text="pass.holder_name"></td>
                                    <td class="px-4 py-3 text-sm font-mono" x-text="pass.truck_placa || '-'"></td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <div x-text="new Date(pass.valid_from).toLocaleDateString()"></div>
                                        <div class="text-xs" x-text="'hasta ' + new Date(pass.valid_until).toLocaleDateString()"></div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="px-2 py-1 rounded text-xs font-semibold"
                                              :class="{
                                                  'bg-green-100 text-green-800': pass.status === 'ACTIVO',
                                                  'bg-yellow-100 text-yellow-800': pass.status === 'VENCIDO',
                                                  'bg-red-100 text-red-800': pass.status === 'REVOCADO'
                                              }"
                                              x-text="pass.status"></span>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <button @click="selectedPass = pass; showQr = true" 
                                                class="text-indigo-600 hover:text-indigo-800 font-semibold">
                                            Ver QR
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- QR Modal -->
                <div x-show="showQr" 
                     x-cloak
                     class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
                     @click.self="showQr = false">
                    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-lg font-bold">Código QR del Pase</h4>
                            <button @click="showQr = false" class="text-gray-500 hover:text-gray-700">✕</button>
                        </div>
                        <div class="text-center">
                            <div class="bg-gray-100 p-4 rounded-lg mb-4">
                                <img :src="'data:image/png;base64,' + selectedPass?.qr_code" 
                                     alt="QR Code" 
                                     class="mx-auto"
                                     style="max-width: 300px;">
                            </div>
                            <div class="text-sm text-gray-600 mb-2">Código de Pase:</div>
                            <div class="text-lg font-mono font-bold text-indigo-600" x-text="selectedPass?.pass_code"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Access Permits Demo -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden" x-data="{ permits: @json($accessPermits) }">
            <div class="bg-green-600 text-white px-6 py-4">
                <h3 class="text-lg font-bold flex items-center">
                    <span class="text-2xl mr-3">🔐</span>
                    2. Permisos de Acceso Vinculados a Carga (NUEVO)
                </h3>
                <p class="text-sm text-green-100 mt-1">Control de permisos de entrada/salida asociados a ítems de carga específicos</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                        <div class="text-2xl font-bold text-yellow-600" x-text="permits.filter(p => p.status === 'PENDIENTE').length"></div>
                        <div class="text-sm text-gray-600">Permisos Pendientes</div>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                        <div class="text-2xl font-bold text-green-600" x-text="permits.filter(p => p.status === 'USADO').length"></div>
                        <div class="text-sm text-gray-600">Permisos Usados</div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <div class="text-2xl font-bold text-gray-600" x-text="permits.filter(p => p.status === 'VENCIDO').length"></div>
                        <div class="text-sm text-gray-600">Permisos Vencidos</div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pase Digital</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contenedor</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Autorizado</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usado</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="permit in permits" :key="permit.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-mono text-indigo-600" x-text="permit.pass_code"></td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="px-2 py-1 rounded text-xs font-semibold"
                                              :class="permit.permit_type === 'SALIDA' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800'"
                                              x-text="permit.permit_type"></span>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-mono font-bold" x-text="permit.container_number || '-'"></td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <div x-show="permit.authorized_at" x-text="permit.authorized_at ? new Date(permit.authorized_at).toLocaleString() : '-'"></div>
                                        <div x-show="!permit.authorized_at" class="text-gray-400">-</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <div x-show="permit.used_at" x-text="permit.used_at ? new Date(permit.used_at).toLocaleString() : '-'"></div>
                                        <div x-show="!permit.used_at" class="text-gray-400">-</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="px-2 py-1 rounded text-xs font-semibold"
                                              :class="{
                                                  'bg-yellow-100 text-yellow-800': permit.status === 'PENDIENTE',
                                                  'bg-green-100 text-green-800': permit.status === 'USADO',
                                                  'bg-gray-100 text-gray-800': permit.status === 'VENCIDO'
                                              }"
                                              x-text="permit.status"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 3. Antepuerto Queue Demo -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden" x-data="{ 
            queues: @json($antepuertoQueues),
            stats: {
                enEspera: @json($antepuertoQueues).filter(q => q.status === 'EN_ESPERA').length,
                autorizado: @json($antepuertoQueues).filter(q => q.status === 'AUTORIZADO').length,
                rechazado: @json($antepuertoQueues).filter(q => q.status === 'RECHAZADO').length,
                avgWaitTime: Math.round(@json($avgWaitTime))
            }
        }">
            <div class="bg-orange-600 text-white px-6 py-4">
                <h3 class="text-lg font-bold flex items-center">
                    <span class="text-2xl mr-3">🚛</span>
                    3. Gestión de Cola de Antepuerto y ZOE (NUEVO)
                </h3>
                <p class="text-sm text-orange-100 mt-1">Control de flujo vehicular con tiempos de espera y autorización de ingreso</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                        <div class="text-2xl font-bold text-blue-600" x-text="stats.enEspera"></div>
                        <div class="text-sm text-gray-600">En Espera</div>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                        <div class="text-2xl font-bold text-green-600" x-text="stats.autorizado"></div>
                        <div class="text-sm text-gray-600">Autorizados</div>
                    </div>
                    <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                        <div class="text-2xl font-bold text-red-600" x-text="stats.rechazado"></div>
                        <div class="text-sm text-gray-600">Rechazados</div>
                    </div>
                    <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                        <div class="text-2xl font-bold text-purple-600" x-text="stats.avgWaitTime + ' min'"></div>
                        <div class="text-sm text-gray-600">Tiempo Promedio</div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Placa</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Zona</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Entrada</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Salida</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tiempo Espera</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="queue in queues" :key="queue.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-mono font-bold" x-text="queue.truck_placa"></td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="px-2 py-1 rounded text-xs font-semibold"
                                              :class="queue.zone === 'ANTEPUERTO' ? 'bg-indigo-100 text-indigo-800' : 'bg-teal-100 text-teal-800'"
                                              x-text="queue.zone"></span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <div x-show="queue.entry_time" x-text="queue.entry_time ? new Date(queue.entry_time).toLocaleTimeString() : '-'"></div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <div x-show="queue.exit_time" x-text="queue.exit_time ? new Date(queue.exit_time).toLocaleTimeString() : '-'"></div>
                                        <div x-show="!queue.exit_time" class="text-blue-600 font-semibold">En cola</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="font-semibold" 
                                              :class="queue.waiting_time > 60 ? 'text-red-600' : 'text-gray-600'"
                                              x-text="queue.waiting_time ? queue.waiting_time + ' min' : '-'"></span>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="px-2 py-1 rounded text-xs font-semibold"
                                              :class="{
                                                  'bg-blue-100 text-blue-800': queue.status === 'EN_ESPERA',
                                                  'bg-green-100 text-green-800': queue.status === 'AUTORIZADO',
                                                  'bg-red-100 text-red-800': queue.status === 'RECHAZADO'
                                              }"
                                              x-text="queue.status"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Key Features Summary -->
        <div class="bg-gradient-to-r from-purple-600 to-pink-600 rounded-lg shadow-lg p-6 text-white">
            <h3 class="text-xl font-bold mb-4 flex items-center">
                <span class="text-2xl mr-3">💡</span>
                Características Clave Implementadas
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white bg-opacity-20 rounded-lg p-4">
                    <h4 class="font-bold mb-2">🎫 Pases Digitales</h4>
                    <ul class="text-sm space-y-1 text-purple-100">
                        <li>• Generación automática de códigos QR únicos</li>
                        <li>• Validación de vigencia temporal</li>
                        <li>• Soporte para acceso personal y vehicular</li>
                        <li>• Estados: Activo, Vencido, Revocado</li>
                    </ul>
                </div>
                <div class="bg-white bg-opacity-20 rounded-lg p-4">
                    <h4 class="font-bold mb-2">🔐 Permisos de Acceso</h4>
                    <ul class="text-sm space-y-1 text-purple-100">
                        <li>• Vinculación con ítems de carga específicos</li>
                        <li>• Control de permisos de entrada/salida</li>
                        <li>• Trazabilidad de autorización y uso</li>
                        <li>• Estados: Pendiente, Usado, Vencido</li>
                    </ul>
                </div>
                <div class="bg-white bg-opacity-20 rounded-lg p-4">
                    <h4 class="font-bold mb-2">🚛 Cola de Antepuerto</h4>
                    <ul class="text-sm space-y-1 text-purple-100">
                        <li>• Gestión de zonas: Antepuerto y ZOE</li>
                        <li>• Cálculo automático de tiempos de espera</li>
                        <li>• Control de autorización de ingreso</li>
                        <li>• Vinculación con citas programadas</li>
                    </ul>
                </div>
                <div class="bg-white bg-opacity-20 rounded-lg p-4">
                    <h4 class="font-bold mb-2">🔗 Integraciones</h4>
                    <ul class="text-sm space-y-1 text-purple-100">
                        <li>• Relación con modelos existentes (Truck, Appointment)</li>
                        <li>• Vinculación con gestión de carga (CargoItem)</li>
                        <li>• Preparado para OCR/LPR (próxima tarea)</li>
                        <li>• Base para validación automática de permisos</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
@endsection
