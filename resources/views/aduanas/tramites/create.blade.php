@extends('layouts.app')

@section('title', 'Crear Trámite Aduanero')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Crear Trámite Aduanero</h2>
            <a href="{{ route('tramites.index') }}" 
               class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded transition-colors">
                Volver
            </a>
        </div>

        <form action="{{ route('tramites.store') }}" 
              method="POST" 
              x-data="tramiteForm()"
              @submit="validateForm($event)">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Tramite External ID -->
                <div class="col-span-2">
                    <label for="tramite_ext_id" class="block text-sm font-medium text-gray-700 mb-2">
                        ID Externo del Trámite <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="tramite_ext_id" 
                           id="tramite_ext_id" 
                           required
                           maxlength="50"
                           value="{{ old('tramite_ext_id') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('tramite_ext_id') border-red-500 @enderror"
                           placeholder="Ej: CUS-2025-001">
                    @error('tramite_ext_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Identificador único del trámite en el sistema externo</p>
                </div>

                <!-- Vessel Call Selection -->
                <div class="col-span-2">
                    <label for="vessel_call_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Llamada de Nave <span class="text-red-500">*</span>
                    </label>
                    <select name="vessel_call_id" 
                            id="vessel_call_id" 
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('vessel_call_id') border-red-500 @enderror">
                        <option value="">Seleccione una llamada de nave</option>
                        @foreach(\App\Models\VesselCall::with('vessel')->whereIn('estado_llamada', ['PROGRAMADA', 'EN_CURSO', 'COMPLETADA'])->orderBy('eta', 'desc')->get() as $vesselCall)
                            <option value="{{ $vesselCall->id }}" {{ old('vessel_call_id') == $vesselCall->id ? 'selected' : '' }}>
                                {{ $vesselCall->vessel->name ?? 'N/A' }} - {{ $vesselCall->viaje_id }} (ETA: {{ $vesselCall->eta?->format('Y-m-d H:i') }})
                            </option>
                        @endforeach
                    </select>
                    @error('vessel_call_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Asocie el trámite con una llamada de nave</p>
                </div>

                <!-- Regimen -->
                <div>
                    <label for="regimen" class="block text-sm font-medium text-gray-700 mb-2">
                        Régimen <span class="text-red-500">*</span>
                    </label>
                    <select name="regimen" 
                            id="regimen" 
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('regimen') border-red-500 @enderror">
                        <option value="">Seleccione un régimen</option>
                        <option value="IMPORTACION" {{ old('regimen') == 'IMPORTACION' ? 'selected' : '' }}>Importación</option>
                        <option value="EXPORTACION" {{ old('regimen') == 'EXPORTACION' ? 'selected' : '' }}>Exportación</option>
                        <option value="TRANSITO" {{ old('regimen') == 'TRANSITO' ? 'selected' : '' }}>Tránsito</option>
                    </select>
                    @error('regimen')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Subpartida -->
                <div>
                    <label for="subpartida" class="block text-sm font-medium text-gray-700 mb-2">
                        Subpartida Arancelaria
                    </label>
                    <input type="text" 
                           name="subpartida" 
                           id="subpartida" 
                           maxlength="20"
                           value="{{ old('subpartida') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('subpartida') border-red-500 @enderror"
                           placeholder="Ej: 8703.23.00.00">
                    @error('subpartida')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Código de clasificación arancelaria</p>
                </div>

                <!-- Entidad Aduanera -->
                <div class="col-span-2">
                    <label for="entidad_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Entidad Aduanera <span class="text-red-500">*</span>
                    </label>
                    <select name="entidad_id" 
                            id="entidad_id" 
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('entidad_id') border-red-500 @enderror">
                        <option value="">Seleccione una entidad</option>
                        @foreach(\App\Models\Entidad::orderBy('name')->get() as $entidad)
                            <option value="{{ $entidad->id }}" {{ old('entidad_id') == $entidad->id ? 'selected' : '' }}>
                                {{ $entidad->name }} ({{ $entidad->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('entidad_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Entidad aduanera responsable del trámite</p>
                </div>

                <!-- Estado -->
                <div>
                    <label for="estado" class="block text-sm font-medium text-gray-700 mb-2">
                        Estado <span class="text-red-500">*</span>
                    </label>
                    <select name="estado" 
                            id="estado" 
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('estado') border-red-500 @enderror">
                        <option value="">Seleccione un estado</option>
                        <option value="INICIADO" {{ old('estado', 'INICIADO') == 'INICIADO' ? 'selected' : '' }}>Iniciado</option>
                        <option value="EN_REVISION" {{ old('estado') == 'EN_REVISION' ? 'selected' : '' }}>En Revisión</option>
                        <option value="OBSERVADO" {{ old('estado') == 'OBSERVADO' ? 'selected' : '' }}>Observado</option>
                        <option value="APROBADO" {{ old('estado') == 'APROBADO' ? 'selected' : '' }}>Aprobado</option>
                        <option value="RECHAZADO" {{ old('estado') == 'RECHAZADO' ? 'selected' : '' }}>Rechazado</option>
                    </select>
                    @error('estado')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Fecha Inicio -->
                <div>
                    <label for="fecha_inicio" class="block text-sm font-medium text-gray-700 mb-2">
                        Fecha de Inicio <span class="text-red-500">*</span>
                    </label>
                    <input type="datetime-local" 
                           name="fecha_inicio" 
                           id="fecha_inicio" 
                           required
                           x-model="fechaInicio"
                           value="{{ old('fecha_inicio') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('fecha_inicio') border-red-500 @enderror">
                    @error('fecha_inicio')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Fecha y hora de inicio del trámite</p>
                </div>

                <!-- Fecha Fin -->
                <div>
                    <label for="fecha_fin" class="block text-sm font-medium text-gray-700 mb-2">
                        Fecha de Fin (Opcional)
                    </label>
                    <input type="datetime-local" 
                           name="fecha_fin" 
                           id="fecha_fin"
                           x-model="fechaFin"
                           value="{{ old('fecha_fin') }}"
                           :class="getFieldClass('fecha_fin', 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('fecha_fin') border-red-500 @enderror')">
                    @error('fecha_fin')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p x-show="hasError('fecha_fin')" x-text="getError('fecha_fin')" class="mt-1 text-sm text-red-600"></p>
                    <p class="mt-1 text-xs text-gray-500">Debe ser posterior o igual a la fecha de inicio</p>
                </div>
            </div>

            <!-- Validation Messages -->
            <div x-show="validationError" 
                 x-transition
                 class="mt-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <p x-text="validationError"></p>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-4 mt-6 pt-6 border-t border-gray-200">
                <a href="{{ route('tramites.index') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancelar
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                    Crear Trámite
                </button>
            </div>
        </form>
    </div>

    <!-- Help Section -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 class="font-semibold text-blue-900 mb-2">Ayuda</h3>
        <ul class="text-sm text-blue-800 space-y-1">
            <li><strong>ID Externo:</strong> Identificador único del trámite en el sistema aduanero externo</li>
            <li><strong>Llamada de Nave:</strong> Operación de nave asociada al trámite</li>
            <li><strong>Régimen:</strong> Tipo de operación aduanera (Importación, Exportación, Tránsito)</li>
            <li><strong>Subpartida:</strong> Código de clasificación arancelaria del producto</li>
            <li><strong>Entidad Aduanera:</strong> Organismo responsable del procesamiento del trámite</li>
            <li><strong>Estados:</strong> INICIADO (nuevo), EN_REVISION (en proceso), OBSERVADO (con observaciones), APROBADO (completado), RECHAZADO (denegado)</li>
        </ul>
    </div>
</div>

<script>
function tramiteForm() {
    return {
        fechaInicio: '{{ old('fecha_inicio') }}',
        fechaFin: '{{ old('fecha_fin') }}',
        validationError: '',
        errors: {},

        hasError(field) {
            return this.errors[field] !== undefined;
        },

        getError(field) {
            return this.errors[field] || '';
        },

        getFieldClass(field, baseClass) {
            return this.hasError(field) ? baseClass + ' border-red-500' : baseClass;
        },

        validateForm(event) {
            this.validationError = '';
            this.errors = {};

            // Validate fecha_fin is after or equal to fecha_inicio
            if (this.fechaFin && this.fechaInicio) {
                const inicio = new Date(this.fechaInicio);
                const fin = new Date(this.fechaFin);
                
                if (fin < inicio) {
                    this.errors.fecha_fin = 'La fecha de fin debe ser posterior o igual a la fecha de inicio';
                    this.validationError = 'Por favor corrija los errores en el formulario';
                    event.preventDefault();
                    return false;
                }
            }

            return true;
        }
    };
}
</script>
@endsection
