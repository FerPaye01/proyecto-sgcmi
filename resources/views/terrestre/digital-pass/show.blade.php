@extends('layouts.app')

@section('title', 'Detalle del Pase Digital')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Pase Digital #{{ $digitalPass->pass_code }}</h1>
        <a href="{{ route('digital-pass.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
            ← Volver al Listado
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Información del Pase -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Información del Pase</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Código del Pase -->
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Código del Pase</label>
                        <p class="text-lg font-mono font-semibold text-gray-900">{{ $digitalPass->pass_code }}</p>
                    </div>

                    <!-- Tipo de Pase -->
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Tipo de Pase</label>
                        <p class="text-lg text-gray-900">
                            @if($digitalPass->pass_type === 'PERSONAL')
                                <span class="px-3 py-1 text-sm font-semibold rounded bg-purple-100 text-purple-800">👤 Personal</span>
                            @else
                                <span class="px-3 py-1 text-sm font-semibold rounded bg-blue-100 text-blue-800">🚛 Vehicular</span>
                            @endif
                        </p>
                    </div>

                    <!-- Estado -->
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Estado</label>
                        <p class="text-lg">
                            @if($digitalPass->status === 'ACTIVO')
                                <span class="px-3 py-1 text-sm font-semibold rounded bg-green-100 text-green-800">✓ Activo</span>
                            @elseif($digitalPass->status === 'VENCIDO')
                                <span class="px-3 py-1 text-sm font-semibold rounded bg-yellow-100 text-yellow-800">⏰ Vencido</span>
                            @elseif($digitalPass->status === 'REVOCADO')
                                <span class="px-3 py-1 text-sm font-semibold rounded bg-red-100 text-red-800">✗ Revocado</span>
                            @else
                                <span class="px-3 py-1 text-sm font-semibold rounded bg-gray-100 text-gray-800">{{ $digitalPass->status }}</span>
                            @endif
                        </p>
                    </div>

                    <!-- Validez -->
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Validez Actual</label>
                        <p class="text-lg">
                            @if($digitalPass->isValid())
                                <span class="px-3 py-1 text-sm font-semibold rounded bg-green-100 text-green-800">✓ Válido</span>
                            @else
                                <span class="px-3 py-1 text-sm font-semibold rounded bg-red-100 text-red-800">✗ No Válido</span>
                            @endif
                        </p>
                    </div>
                </div>

                <hr class="my-6">

                <h3 class="text-lg font-semibold text-gray-900 mb-4">Datos del Titular</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Nombre del Titular -->
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Nombre Completo</label>
                        <p class="text-lg text-gray-900">{{ $digitalPass->holder_name }}</p>
                    </div>

                    <!-- DNI del Titular -->
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">DNI</label>
                        <p class="text-lg text-gray-900">{{ $digitalPass->holder_dni }}</p>
                    </div>

                    @if($digitalPass->truck)
                        <!-- Placa del Vehículo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Placa del Vehículo</label>
                            <p class="text-lg font-semibold text-gray-900">{{ $digitalPass->truck->placa }}</p>
                        </div>

                        <!-- Empresa -->
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Empresa</label>
                            <p class="text-lg text-gray-900">{{ $digitalPass->truck->company->nombre ?? 'N/A' }}</p>
                        </div>
                    @endif
                </div>

                <hr class="my-6">

                <h3 class="text-lg font-semibold text-gray-900 mb-4">Período de Validez</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Válido Desde -->
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Válido Desde</label>
                        <p class="text-lg text-gray-900">{{ $digitalPass->valid_from->format('d/m/Y H:i') }}</p>
                    </div>

                    <!-- Válido Hasta -->
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Válido Hasta</label>
                        <p class="text-lg text-gray-900">{{ $digitalPass->valid_until->format('d/m/Y H:i') }}</p>
                    </div>
                </div>

                <hr class="my-6">

                <h3 class="text-lg font-semibold text-gray-900 mb-4">Información de Auditoría</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Creado Por -->
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Creado Por</label>
                        <p class="text-lg text-gray-900">{{ $digitalPass->creator->full_name ?? $digitalPass->creator->username ?? 'N/A' }}</p>
                    </div>

                    <!-- Fecha de Creación -->
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Fecha de Creación</label>
                        <p class="text-lg text-gray-900">{{ $digitalPass->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>

                <!-- Acciones -->
                <div class="mt-6 flex space-x-3">
                    @if($digitalPass->status === 'ACTIVO')
                        <form method="POST" action="{{ route('digital-pass.revoke', $digitalPass) }}" onsubmit="return confirm('¿Está seguro de revocar este pase digital?')">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-md font-medium">
                                Revocar Pase
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Permisos de Acceso Asociados -->
            @if($digitalPass->accessPermits->count() > 0)
                <div class="bg-white rounded-lg shadow p-6 mt-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Permisos de Acceso Asociados</h2>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Carga</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Autorizado</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($digitalPass->accessPermits as $permit)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $permit->permit_type }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $permit->cargoItem->description ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $permit->authorized_at?->format('d/m/Y H:i') ?? 'Pendiente' }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            @if($permit->status === 'USADO')
                                                <span class="px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-800">Usado</span>
                                            @elseif($permit->status === 'PENDIENTE')
                                                <span class="px-2 py-1 text-xs font-semibold rounded bg-yellow-100 text-yellow-800">Pendiente</span>
                                            @else
                                                <span class="px-2 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-800">{{ $permit->status }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <!-- Código QR -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow p-6 sticky top-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4 text-center">Código QR</h2>
                
                <div class="flex justify-center mb-4">
                    @if($digitalPass->qr_code)
                        <img src="{{ $digitalPass->qr_code }}" alt="QR Code" class="w-64 h-64 border-4 border-gray-200 rounded-lg">
                    @else
                        <div class="w-64 h-64 bg-gray-100 border-4 border-gray-200 rounded-lg flex items-center justify-center">
                            <p class="text-gray-500 text-center">QR no disponible</p>
                        </div>
                    @endif
                </div>

                <div class="text-center">
                    <p class="text-sm text-gray-600 mb-2">Escanee este código para validar el pase</p>
                    <p class="text-xs font-mono text-gray-500 break-all">{{ $digitalPass->pass_code }}</p>
                </div>

                @if($digitalPass->isValid())
                    <div class="mt-4 bg-green-50 border border-green-200 rounded-lg p-3">
                        <p class="text-sm text-green-800 text-center font-semibold">✓ Pase Válido</p>
                        <p class="text-xs text-green-700 text-center mt-1">
                            Válido hasta: {{ $digitalPass->valid_until->format('d/m/Y H:i') }}
                        </p>
                    </div>
                @else
                    <div class="mt-4 bg-red-50 border border-red-200 rounded-lg p-3">
                        <p class="text-sm text-red-800 text-center font-semibold">✗ Pase No Válido</p>
                        @if($digitalPass->status === 'REVOCADO')
                            <p class="text-xs text-red-700 text-center mt-1">El pase ha sido revocado</p>
                        @elseif($digitalPass->valid_until < now())
                            <p class="text-xs text-red-700 text-center mt-1">El pase ha expirado</p>
                        @elseif($digitalPass->valid_from > now())
                            <p class="text-xs text-red-700 text-center mt-1">El pase aún no es válido</p>
                        @endif
                    </div>
                @endif

                <!-- Botón de Impresión -->
                <div class="mt-4">
                    <button onclick="window.print()" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium">
                        🖨️ Imprimir Pase
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    nav, footer, .no-print {
        display: none !important;
    }
    .container {
        max-width: 100% !important;
    }
}
</style>
@endsection
