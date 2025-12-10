@extends('layouts.app')

@section('title', 'Validar Arribo de Nave')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Validar Arribo de Nave</h1>
            <a href="{{ route('vessel-planning.show', $vesselCall) }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                Volver
            </a>
        </div>

        <!-- Vessel Information Summary -->
        <div class="bg-blue-50 rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-3">Información de la Nave</h2>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="font-medium">Nave:</span> {{ $vesselCall->vessel->name }}
                </div>
                <div>
                    <span class="font-medium">Viaje:</span> {{ $vesselCall->viaje_id ?? 'N/A' }}
                </div>
                <div>
                    <span class="font-medium">ETA:</span> {{ $vesselCall->eta->format('d/m/Y H:i') }}
                </div>
                <div>
                    <span class="font-medium">Muelle:</span> {{ $vesselCall->berth->name ?? 'N/A' }}
                </div>
            </div>
        </div>

        <!-- Ship Particulars Summary -->
        @if($vesselCall->shipParticulars)
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-3">Particulares de la Nave</h2>
            <div class="grid grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="font-medium">LOA:</span> {{ $vesselCall->shipParticulars->loa }} m
                </div>
                <div>
                    <span class="font-medium">Manga:</span> {{ $vesselCall->shipParticulars->beam }} m
                </div>
                <div>
                    <span class="font-medium">Calado:</span> {{ $vesselCall->shipParticulars->draft }} m
                </div>
                @if($vesselCall->shipParticulars->grt)
                <div>
                    <span class="font-medium">GRT:</span> {{ number_format($vesselCall->shipParticulars->grt, 2) }}
                </div>
                @endif
                @if($vesselCall->shipParticulars->dwt)
                <div>
                    <span class="font-medium">DWT:</span> {{ number_format($vesselCall->shipParticulars->dwt, 2) }}
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

        <!-- Validation Form -->
        <form action="{{ route('vessel-planning.validate-arrival', $vesselCall) }}" method="POST" 
              x-data="{ approvalStatus: '{{ old('approval_status', 'APPROVED') }}' }">
            @csrf

            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Verificaciones de Seguridad</h2>
                
                <div class="space-y-4">
                    <!-- Safety Check -->
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="safety_check" id="safety_check" value="1"
                                   {{ old('safety_check') ? 'checked' : '' }}
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        </div>
                        <div class="ml-3">
                            <label for="safety_check" class="font-medium text-gray-700">Verificación de Seguridad</label>
                            <p class="text-sm text-gray-500">Condiciones de seguridad de la nave verificadas (PBIP, certificados, etc.)</p>
                        </div>
                    </div>

                    <!-- Stowage Check -->
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="stowage_check" id="stowage_check" value="1"
                                   {{ old('stowage_check') ? 'checked' : '' }}
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        </div>
                        <div class="ml-3">
                            <label for="stowage_check" class="font-medium text-gray-700">Verificación de Estiba</label>
                            <p class="text-sm text-gray-500">Plan de estiba revisado y aprobado</p>
                        </div>
                    </div>

                    <!-- Cargo Type Check -->
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="cargo_type_check" id="cargo_type_check" value="1"
                                   {{ old('cargo_type_check') ? 'checked' : '' }}
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        </div>
                        <div class="ml-3">
                            <label for="cargo_type_check" class="font-medium text-gray-700">Verificación de Tipo de Carga</label>
                            <p class="text-sm text-gray-500">Tipo de carga compatible con instalaciones del terminal</p>
                        </div>
                    </div>

                    <!-- Particulars Check -->
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="particulars_check" id="particulars_check" value="1"
                                   {{ old('particulars_check') ? 'checked' : '' }}
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        </div>
                        <div class="ml-3">
                            <label for="particulars_check" class="font-medium text-gray-700">Verificación de Particulares</label>
                            <p class="text-sm text-gray-500">Dimensiones y características de la nave compatibles con muelle asignado</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Approval Decision -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Decisión de Aprobación</h2>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Estado de Aprobación *</label>
                        <div class="space-y-2">
                            <label class="inline-flex items-center">
                                <input type="radio" name="approval_status" value="APPROVED" 
                                       x-model="approvalStatus"
                                       {{ old('approval_status', 'APPROVED') == 'APPROVED' ? 'checked' : '' }}
                                       class="w-4 h-4 text-green-600 border-gray-300 focus:ring-green-500">
                                <span class="ml-2 text-green-700 font-medium">✓ Aprobar Arribo</span>
                            </label>
                            <br>
                            <label class="inline-flex items-center">
                                <input type="radio" name="approval_status" value="REJECTED" 
                                       x-model="approvalStatus"
                                       {{ old('approval_status') == 'REJECTED' ? 'checked' : '' }}
                                       class="w-4 h-4 text-red-600 border-gray-300 focus:ring-red-500">
                                <span class="ml-2 text-red-700 font-medium">✗ Rechazar Arribo</span>
                            </label>
                        </div>
                        @error('approval_status')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="approval_reason" class="block text-sm font-medium text-gray-700">
                            <span x-show="approvalStatus === 'APPROVED'">Observaciones de Aprobación *</span>
                            <span x-show="approvalStatus === 'REJECTED'">Motivo de Rechazo *</span>
                        </label>
                        <textarea name="approval_reason" id="approval_reason" rows="4" required
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                  placeholder="Ingrese las observaciones o motivo...">{{ old('approval_reason') }}</textarea>
                        @error('approval_reason')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex justify-end space-x-3">
                <a href="{{ route('vessel-planning.show', $vesselCall) }}" 
                   class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg font-medium">
                    Cancelar
                </a>
                <button type="submit" 
                        class="px-6 py-2 rounded-lg font-medium text-white"
                        :class="approvalStatus === 'APPROVED' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'">
                    <span x-show="approvalStatus === 'APPROVED'">Aprobar Arribo</span>
                    <span x-show="approvalStatus === 'REJECTED'">Rechazar Arribo</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
