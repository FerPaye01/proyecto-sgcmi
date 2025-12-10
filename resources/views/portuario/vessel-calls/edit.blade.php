@extends('layouts.app')

@section('title', 'Editar Llamada de Nave')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Editar Llamada de Nave</h2>
            <a href="{{ route('vessel-calls.index') }}" 
               class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded transition-colors">
                Volver
            </a>
        </div>

        <form action="{{ route('vessel-calls.update', $vesselCall->id) }}" 
              method="POST" 
              x-data="vesselCallForm({
                  eta: '{{ old('eta', $vesselCall->eta ? $vesselCall->eta->format('Y-m-d\TH:i') : '') }}',
                  etb: '{{ old('etb', $vesselCall->etb ? $vesselCall->etb->format('Y-m-d\TH:i') : '') }}',
                  ata: '{{ old('ata', $vesselCall->ata ? $vesselCall->ata->format('Y-m-d\TH:i') : '') }}',
                  atb: '{{ old('atb', $vesselCall->atb ? $vesselCall->atb->format('Y-m-d\TH:i') : '') }}',
                  atd: '{{ old('atd', $vesselCall->atd ? $vesselCall->atd->format('Y-m-d\TH:i') : '') }}'
              })"
              @submit="validateDates($event)">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Vessel Selection -->
                <div class="col-span-2">
                    <label for="vessel_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Nave <span class="text-red-500">*</span>
                    </label>
                    <select name="vessel_id" 
                            id="vessel_id" 
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('vessel_id') border-red-500 @enderror">
                        <option value="">Seleccione una nave</option>
                        @foreach(\App\Models\Vessel::orderBy('name')->get() as $vessel)
                            <option value="{{ $vessel->id }}" 
                                {{ (old('vessel_id', $vesselCall->vessel_id) == $vessel->id) ? 'selected' : '' }}>
                                {{ $vessel->name }} ({{ $vessel->imo }})
                            </option>
                        @endforeach
                    </select>
                    @error('vessel_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Viaje ID -->
                <div>
                    <label for="viaje_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Viaje ID
                    </label>
                    <input type="text" 
                           name="viaje_id" 
                           id="viaje_id" 
                           value="{{ old('viaje_id', $vesselCall->viaje_id) }}"
                           maxlength="255"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('viaje_id') border-red-500 @enderror"
                           placeholder="Ej: V2025-001">
                    @error('viaje_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Berth Selection -->
                <div>
                    <label for="berth_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Muelle
                    </label>
                    <select name="berth_id" 
                            id="berth_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('berth_id') border-red-500 @enderror">
                        <option value="">Seleccione un muelle</option>
                        @foreach(\App\Models\Berth::where('active', true)->orderBy('name')->get() as $berth)
                            <option value="{{ $berth->id }}" 
                                {{ (old('berth_id', $vesselCall->berth_id) == $berth->id) ? 'selected' : '' }}>
                                {{ $berth->name }} ({{ $berth->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('berth_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- ETA (Estimated Time of Arrival) -->
                <div>
                    <label for="eta" class="block text-sm font-medium text-gray-700 mb-2">
                        ETA (Hora Estimada de Arribo) <span class="text-red-500">*</span>
                    </label>
                    <input type="datetime-local" 
                           name="eta" 
                           id="eta" 
                           required
                           x-model="eta"
                           value="{{ old('eta', $vesselCall->eta ? $vesselCall->eta->format('Y-m-d\TH:i') : '') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('eta') border-red-500 @enderror">
                    @error('eta')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Hora estimada de llegada al puerto</p>
                </div>

                <!-- ETB (Estimated Time of Berthing) -->
                <div>
                    <label for="etb" class="block text-sm font-medium text-gray-700 mb-2">
                        ETB (Hora Estimada de Atraque)
                    </label>
                    <input type="datetime-local" 
                           name="etb" 
                           id="etb"
                           x-model="etb"
                           value="{{ old('etb', $vesselCall->etb ? $vesselCall->etb->format('Y-m-d\TH:i') : '') }}"
                           :class="getFieldClass('etb', 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('etb') border-red-500 @enderror')">
                    @error('etb')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p x-show="hasError('etb')" x-text="getError('etb')" class="mt-1 text-sm text-red-600"></p>
                    <p class="mt-1 text-xs text-gray-500">Debe ser mayor o igual a ETA</p>
                </div>

                <!-- ATA (Actual Time of Arrival) -->
                <div>
                    <label for="ata" class="block text-sm font-medium text-gray-700 mb-2">
                        ATA (Hora Real de Arribo)
                    </label>
                    <input type="datetime-local" 
                           name="ata" 
                           id="ata"
                           x-model="ata"
                           value="{{ old('ata', $vesselCall->ata ? $vesselCall->ata->format('Y-m-d\TH:i') : '') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('ata') border-red-500 @enderror">
                    @error('ata')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Hora real de llegada al puerto</p>
                </div>

                <!-- ATB (Actual Time of Berthing) -->
                <div>
                    <label for="atb" class="block text-sm font-medium text-gray-700 mb-2">
                        ATB (Hora Real de Atraque)
                    </label>
                    <input type="datetime-local" 
                           name="atb" 
                           id="atb"
                           x-model="atb"
                           value="{{ old('atb', $vesselCall->atb ? $vesselCall->atb->format('Y-m-d\TH:i') : '') }}"
                           :class="getFieldClass('atb', 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('atb') border-red-500 @enderror')">
                    @error('atb')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p x-show="hasError('atb')" x-text="getError('atb')" class="mt-1 text-sm text-red-600"></p>
                    <p class="mt-1 text-xs text-gray-500">Debe ser mayor o igual a ATA</p>
                </div>

                <!-- ATD (Actual Time of Departure) -->
                <div>
                    <label for="atd" class="block text-sm font-medium text-gray-700 mb-2">
                        ATD (Hora Real de Salida)
                    </label>
                    <input type="datetime-local" 
                           name="atd" 
                           id="atd"
                           x-model="atd"
                           value="{{ old('atd', $vesselCall->atd ? $vesselCall->atd->format('Y-m-d\TH:i') : '') }}"
                           :class="getFieldClass('atd', 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('atd') border-red-500 @enderror')">
                    @error('atd')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p x-show="hasError('atd')" x-text="getError('atd')" class="mt-1 text-sm text-red-600"></p>
                    <p class="mt-1 text-xs text-gray-500">Debe ser mayor o igual a ATB</p>
                </div>

                <!-- Estado Llamada -->
                <div>
                    <label for="estado_llamada" class="block text-sm font-medium text-gray-700 mb-2">
                        Estado <span class="text-red-500">*</span>
                    </label>
                    <select name="estado_llamada" 
                            id="estado_llamada" 
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('estado_llamada') border-red-500 @enderror">
                        <option value="">Seleccione un estado</option>
                        <option value="PROGRAMADA" {{ old('estado_llamada', $vesselCall->estado_llamada) == 'PROGRAMADA' ? 'selected' : '' }}>Programada</option>
                        <option value="EN_CURSO" {{ old('estado_llamada', $vesselCall->estado_llamada) == 'EN_CURSO' ? 'selected' : '' }}>En Curso</option>
                        <option value="COMPLETADA" {{ old('estado_llamada', $vesselCall->estado_llamada) == 'COMPLETADA' ? 'selected' : '' }}>Completada</option>
                        <option value="CANCELADA" {{ old('estado_llamada', $vesselCall->estado_llamada) == 'CANCELADA' ? 'selected' : '' }}>Cancelada</option>
                    </select>
                    @error('estado_llamada')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Motivo Demora -->
                <div class="col-span-2">
                    <label for="motivo_demora" class="block text-sm font-medium text-gray-700 mb-2">
                        Motivo de Demora
                    </label>
                    <textarea name="motivo_demora" 
                              id="motivo_demora" 
                              rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('motivo_demora') border-red-500 @enderror"
                              placeholder="Describa el motivo de la demora si aplica">{{ old('motivo_demora', $vesselCall->motivo_demora) }}</textarea>
                    @error('motivo_demora')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
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
                <a href="{{ route('vessel-calls.index') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancelar
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                    Actualizar Llamada
                </button>
            </div>
        </form>
    </div>

    <!-- Help Section -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 class="font-semibold text-blue-900 mb-2">Ayuda</h3>
        <ul class="text-sm text-blue-800 space-y-1">
            <li><strong>ETA:</strong> Hora estimada de arribo al puerto</li>
            <li><strong>ETB:</strong> Hora estimada de atraque en el muelle (debe ser ≥ ETA)</li>
            <li><strong>ATA:</strong> Hora real de arribo al puerto</li>
            <li><strong>ATB:</strong> Hora real de atraque en el muelle (debe ser ≥ ATA)</li>
            <li><strong>ATD:</strong> Hora real de salida del puerto (debe ser ≥ ATB)</li>
        </ul>
    </div>
</div>
@endsection
