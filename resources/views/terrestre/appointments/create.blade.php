@extends('layouts.app')

@section('title', 'Crear Cita de Camión')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Crear Cita de Camión</h2>
            <a href="{{ route('appointments.index') }}" 
               class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded transition-colors">
                Volver
            </a>
        </div>

        <form action="{{ route('appointments.store') }}" 
              method="POST" 
              x-data="appointmentForm()"
              @submit="validateForm($event)">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Company Selection -->
                <div class="col-span-2">
                    <label for="company_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Empresa <span class="text-red-500">*</span>
                    </label>
                    <select name="company_id" 
                            id="company_id" 
                            required
                            x-model="companyId"
                            @change="loadTrucks()"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('company_id') border-red-500 @enderror">
                        <option value="">Seleccione una empresa</option>
                        @foreach(\App\Models\Company::where('active', true)->orderBy('name')->get() as $company)
                            <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                {{ $company->name }} ({{ $company->ruc }})
                            </option>
                        @endforeach
                    </select>
                    @error('company_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Truck Selection -->
                <div class="col-span-2">
                    <label for="truck_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Camión <span class="text-red-500">*</span>
                    </label>
                    <select name="truck_id" 
                            id="truck_id" 
                            required
                            x-model="truckId"
                            :disabled="!companyId"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed @error('truck_id') border-red-500 @enderror">
                        <option value="">Seleccione un camión</option>
                        <template x-for="truck in trucks" :key="truck.id">
                            <option :value="truck.id" x-text="truck.placa"></option>
                        </template>
                    </select>
                    @error('truck_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Primero seleccione una empresa</p>
                </div>

                <!-- Vessel Call Selection (Optional) -->
                <div class="col-span-2">
                    <label for="vessel_call_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Llamada de Nave (Opcional)
                    </label>
                    <select name="vessel_call_id" 
                            id="vessel_call_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('vessel_call_id') border-red-500 @enderror">
                        <option value="">Sin llamada de nave asociada</option>
                        @foreach(\App\Models\VesselCall::with('vessel')->whereIn('estado_llamada', ['PROGRAMADA', 'EN_CURSO'])->orderBy('eta', 'desc')->get() as $vesselCall)
                            <option value="{{ $vesselCall->id }}" {{ old('vessel_call_id') == $vesselCall->id ? 'selected' : '' }}>
                                {{ $vesselCall->vessel->name ?? 'N/A' }} - {{ $vesselCall->viaje_id }} (ETA: {{ $vesselCall->eta?->format('Y-m-d H:i') }})
                            </option>
                        @endforeach
                    </select>
                    @error('vessel_call_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Asocie la cita con una llamada de nave si aplica</p>
                </div>

                <!-- Hora Programada -->
                <div>
                    <label for="hora_programada" class="block text-sm font-medium text-gray-700 mb-2">
                        Hora Programada <span class="text-red-500">*</span>
                    </label>
                    <input type="datetime-local" 
                           name="hora_programada" 
                           id="hora_programada" 
                           required
                           x-model="horaProgramada"
                           value="{{ old('hora_programada') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('hora_programada') border-red-500 @enderror">
                    @error('hora_programada')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Fecha y hora de la cita programada</p>
                </div>

                <!-- Hora Llegada (Optional) -->
                <div>
                    <label for="hora_llegada" class="block text-sm font-medium text-gray-700 mb-2">
                        Hora de Llegada (Opcional)
                    </label>
                    <input type="datetime-local" 
                           name="hora_llegada" 
                           id="hora_llegada"
                           x-model="horaLlegada"
                           value="{{ old('hora_llegada') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('hora_llegada') border-red-500 @enderror">
                    @error('hora_llegada')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Hora real de llegada del camión</p>
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
                        <option value="PROGRAMADA" {{ old('estado') == 'PROGRAMADA' ? 'selected' : '' }}>Programada</option>
                        <option value="CONFIRMADA" {{ old('estado') == 'CONFIRMADA' ? 'selected' : '' }}>Confirmada</option>
                        <option value="ATENDIDA" {{ old('estado') == 'ATENDIDA' ? 'selected' : '' }}>Atendida</option>
                        <option value="NO_SHOW" {{ old('estado') == 'NO_SHOW' ? 'selected' : '' }}>No Show</option>
                        <option value="CANCELADA" {{ old('estado') == 'CANCELADA' ? 'selected' : '' }}>Cancelada</option>
                    </select>
                    @error('estado')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Motivo -->
                <div class="col-span-2">
                    <label for="motivo" class="block text-sm font-medium text-gray-700 mb-2">
                        Motivo / Observaciones
                    </label>
                    <textarea name="motivo" 
                              id="motivo" 
                              rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('motivo') border-red-500 @enderror"
                              placeholder="Ingrese observaciones o motivo de cancelación si aplica">{{ old('motivo') }}</textarea>
                    @error('motivo')
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
                <a href="{{ route('appointments.index') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancelar
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                    Crear Cita
                </button>
            </div>
        </form>
    </div>

    <!-- Help Section -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 class="font-semibold text-blue-900 mb-2">Ayuda</h3>
        <ul class="text-sm text-blue-800 space-y-1">
            <li><strong>Empresa:</strong> Seleccione la empresa transportista</li>
            <li><strong>Camión:</strong> Seleccione el camión de la empresa (se filtra automáticamente)</li>
            <li><strong>Llamada de Nave:</strong> Opcional - asocie la cita con una operación de nave</li>
            <li><strong>Hora Programada:</strong> Fecha y hora en que se espera la llegada del camión</li>
            <li><strong>Estados:</strong> PROGRAMADA (nueva), CONFIRMADA (confirmada por transportista), ATENDIDA (completada), NO_SHOW (no se presentó), CANCELADA</li>
        </ul>
    </div>
</div>

<script>
function appointmentForm() {
    return {
        companyId: '{{ old('company_id') }}',
        truckId: '{{ old('truck_id') }}',
        horaProgramada: '{{ old('hora_programada') }}',
        horaLlegada: '{{ old('hora_llegada') }}',
        trucks: [],
        validationError: '',

        init() {
            // Load trucks if company is pre-selected (from old input)
            if (this.companyId) {
                this.loadTrucks();
            }
        },

        async loadTrucks() {
            if (!this.companyId) {
                this.trucks = [];
                this.truckId = '';
                return;
            }

            try {
                // In a real implementation, this would be an API call
                // For now, we'll use the trucks already loaded in the page
                const allTrucks = @json(\App\Models\Truck::where('activo', true)->get(['id', 'placa', 'company_id']));
                this.trucks = allTrucks.filter(truck => truck.company_id == this.companyId);
                
                // Reset truck selection if current truck doesn't belong to selected company
                if (this.truckId) {
                    const truckExists = this.trucks.find(t => t.id == this.truckId);
                    if (!truckExists) {
                        this.truckId = '';
                    }
                }
            } catch (error) {
                console.error('Error loading trucks:', error);
                this.validationError = 'Error al cargar los camiones';
            }
        },

        validateForm(event) {
            this.validationError = '';

            // Basic validation
            if (!this.companyId) {
                this.validationError = 'Debe seleccionar una empresa';
                event.preventDefault();
                return false;
            }

            if (!this.truckId) {
                this.validationError = 'Debe seleccionar un camión';
                event.preventDefault();
                return false;
            }

            if (!this.horaProgramada) {
                this.validationError = 'Debe ingresar la hora programada';
                event.preventDefault();
                return false;
            }

            // Validate that hora_llegada is not before hora_programada if both are set
            if (this.horaLlegada && this.horaProgramada) {
                const programada = new Date(this.horaProgramada);
                const llegada = new Date(this.horaLlegada);
                
                if (llegada < programada) {
                    this.validationError = 'La hora de llegada no puede ser anterior a la hora programada';
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
