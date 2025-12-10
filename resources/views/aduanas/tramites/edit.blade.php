@extends('layouts.app')

@section('title', 'Editar Trámite')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-3xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Editar Trámite</h1>
            <a href="{{ route('tramites.index') }}" class="text-gray-600 hover:text-gray-900">
                ← Volver al listado
            </a>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <form method="POST" action="{{ route('tramites.update', $tramite) }}">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Trámite Ext ID -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Trámite Externo ID
                        </label>
                        <input type="text" name="tramite_ext_id" value="{{ old('tramite_ext_id', $tramite->tramite_ext_id) }}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md @error('tramite_ext_id') border-red-500 @enderror" 
                               readonly>
                        @error('tramite_ext_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Régimen -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Régimen <span class="text-red-500">*</span>
                        </label>
                        <select name="regimen" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md @error('regimen') border-red-500 @enderror">
                            <option value="">Seleccione...</option>
                            <option value="IMPORTACION" {{ old('regimen', $tramite->regimen) == 'IMPORTACION' ? 'selected' : '' }}>Importación</option>
                            <option value="EXPORTACION" {{ old('regimen', $tramite->regimen) == 'EXPORTACION' ? 'selected' : '' }}>Exportación</option>
                            <option value="TRANSITO" {{ old('regimen', $tramite->regimen) == 'TRANSITO' ? 'selected' : '' }}>Tránsito</option>
                        </select>
                        @error('regimen')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Nave -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Nave <span class="text-red-500">*</span>
                        </label>
                        <select name="vessel_call_id" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md @error('vessel_call_id') border-red-500 @enderror">
                            <option value="">Seleccione...</option>
                            @foreach($vesselCalls as $vc)
                                <option value="{{ $vc->id }}" {{ old('vessel_call_id', $tramite->vessel_call_id) == $vc->id ? 'selected' : '' }}>
                                    {{ $vc->vessel_name }} - {{ \Carbon\Carbon::parse($vc->eta)->format('d/m/Y') }}
                                </option>
                            @endforeach
                        </select>
                        @error('vessel_call_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Entidad -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Entidad <span class="text-red-500">*</span>
                        </label>
                        <select name="entidad_id" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md @error('entidad_id') border-red-500 @enderror">
                            <option value="">Seleccione...</option>
                            @foreach($entidades as $entidad)
                                <option value="{{ $entidad->id }}" {{ old('entidad_id', $tramite->entidad_id) == $entidad->id ? 'selected' : '' }}>
                                    {{ $entidad->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('entidad_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Subpartida -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Subpartida Arancelaria
                        </label>
                        <input type="text" name="subpartida" value="{{ old('subpartida', $tramite->subpartida) }}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md @error('subpartida') border-red-500 @enderror">
                        @error('subpartida')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Estado -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Estado <span class="text-red-500">*</span>
                        </label>
                        <select name="estado" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md @error('estado') border-red-500 @enderror">
                            <option value="INICIADO" {{ old('estado', $tramite->estado) == 'INICIADO' ? 'selected' : '' }}>Iniciado</option>
                            <option value="EN_PROCESO" {{ old('estado', $tramite->estado) == 'EN_PROCESO' ? 'selected' : '' }}>En Proceso</option>
                            <option value="APROBADO" {{ old('estado', $tramite->estado) == 'APROBADO' ? 'selected' : '' }}>Aprobado</option>
                            <option value="RECHAZADO" {{ old('estado', $tramite->estado) == 'RECHAZADO' ? 'selected' : '' }}>Rechazado</option>
                            <option value="COMPLETADO" {{ old('estado', $tramite->estado) == 'COMPLETADO' ? 'selected' : '' }}>Completado</option>
                        </select>
                        @error('estado')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Fecha Inicio -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Fecha Inicio <span class="text-red-500">*</span>
                        </label>
                        <input type="datetime-local" name="fecha_inicio" 
                               value="{{ old('fecha_inicio', \Carbon\Carbon::parse($tramite->fecha_inicio)->format('Y-m-d\TH:i')) }}" 
                               required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md @error('fecha_inicio') border-red-500 @enderror">
                        @error('fecha_inicio')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Fecha Fin -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Fecha Fin
                        </label>
                        <input type="datetime-local" name="fecha_fin" 
                               value="{{ old('fecha_fin', $tramite->fecha_fin ? \Carbon\Carbon::parse($tramite->fecha_fin)->format('Y-m-d\TH:i') : '') }}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md @error('fecha_fin') border-red-500 @enderror">
                        @error('fecha_fin')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Botones -->
                <div class="flex justify-end space-x-3 mt-6">
                    <a href="{{ route('tramites.index') }}" 
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium">
                        Actualizar Trámite
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
