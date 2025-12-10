@extends('layouts.app')

@section('title', 'Editar Junta de Operaciones')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-sgcmi-blue-900">Editar Junta de Operaciones #{{ $operationsMeeting->id }}</h1>
        <p class="text-gray-600 mt-2">Modifique los acuerdos y programación de operaciones</p>
    </div>
    
    <div class="card">
        <form method="POST" action="{{ route('operations-meeting.update', $operationsMeeting) }}" x-data="meetingForm()">
            @csrf
            @method('PATCH')
            
            <!-- Meeting Date and Time -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="meeting_date" class="block text-sm font-medium text-gray-700 mb-1">
                        Fecha de la Junta <span class="text-red-500">*</span>
                    </label>
                    <input type="date" 
                           id="meeting_date" 
                           name="meeting_date" 
                           value="{{ old('meeting_date', $operationsMeeting->meeting_date->format('Y-m-d')) }}"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sgcmi-blue-500 focus:border-transparent @error('meeting_date') border-red-500 @enderror">
                    @error('meeting_date')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="meeting_time" class="block text-sm font-medium text-gray-700 mb-1">
                        Hora de la Junta <span class="text-red-500">*</span>
                    </label>
                    <input type="time" 
                           id="meeting_time" 
                           name="meeting_time" 
                           value="{{ old('meeting_time', $operationsMeeting->meeting_time) }}"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sgcmi-blue-500 focus:border-transparent @error('meeting_time') border-red-500 @enderror">
                    @error('meeting_time')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            
            <!-- Attendees Section -->
            <div class="mb-6">
                <div class="flex justify-between items-center mb-3">
                    <label class="block text-sm font-medium text-gray-700">
                        Asistentes <span class="text-red-500">*</span>
                    </label>
                    <button type="button" 
                            @click="addAttendee()"
                            class="text-sm bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded">
                        + Agregar Asistente
                    </button>
                </div>
                
                <div class="space-y-3">
                    <template x-for="(attendee, index) in attendees" :key="index">
                        <div class="flex gap-3 items-start">
                            <div class="flex-1">
                                <input type="text" 
                                       :name="'attendees[' + index + '][name]'"
                                       x-model="attendee.name"
                                       placeholder="Nombre completo"
                                       required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sgcmi-blue-500 focus:border-transparent">
                            </div>
                            <div class="flex-1">
                                <input type="text" 
                                       :name="'attendees[' + index + '][role]'"
                                       x-model="attendee.role"
                                       placeholder="Cargo/Rol"
                                       required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sgcmi-blue-500 focus:border-transparent">
                            </div>
                            <button type="button" 
                                    @click="removeAttendee(index)"
                                    class="text-red-600 hover:text-red-800 px-2 py-2">
                                ✕
                            </button>
                        </div>
                    </template>
                </div>
                @error('attendees')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Agreements -->
            <div class="mb-6">
                <label for="agreements" class="block text-sm font-medium text-gray-700 mb-1">
                    Acuerdos de la Junta <span class="text-red-500">*</span>
                </label>
                <textarea id="agreements" 
                          name="agreements" 
                          rows="6"
                          required
                          placeholder="Describa los acuerdos alcanzados en la junta de operaciones..."
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sgcmi-blue-500 focus:border-transparent @error('agreements') border-red-500 @enderror">{{ old('agreements', $operationsMeeting->agreements) }}</textarea>
                @error('agreements')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- 24h Schedule Section -->
            <div class="mb-6">
                <div class="flex justify-between items-center mb-3">
                    <label class="block text-sm font-medium text-gray-700">
                        Programación Próximas 24 Horas <span class="text-red-500">*</span>
                    </label>
                    <button type="button" 
                            @click="addScheduleItem()"
                            class="text-sm bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded">
                        + Agregar Operación
                    </button>
                </div>
                
                <div class="space-y-3">
                    <template x-for="(item, index) in scheduleItems" :key="index">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-start p-3 bg-gray-50 rounded-lg">
                            <div>
                                <input type="text" 
                                       :name="'next_24h_schedule[' + index + '][vessel]'"
                                       x-model="item.vessel"
                                       placeholder="Nombre de la nave"
                                       required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sgcmi-blue-500 focus:border-transparent">
                            </div>
                            <div>
                                <select :name="'next_24h_schedule[' + index + '][operation]'"
                                        x-model="item.operation"
                                        required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sgcmi-blue-500 focus:border-transparent">
                                    <option value="">Tipo de operación</option>
                                    <option value="CARGA">CARGA</option>
                                    <option value="DESCARGA">DESCARGA</option>
                                    <option value="REESTIBA">REESTIBA</option>
                                </select>
                            </div>
                            <div>
                                <input type="time" 
                                       :name="'next_24h_schedule[' + index + '][start_time]'"
                                       x-model="item.start_time"
                                       placeholder="Hora inicio"
                                       required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sgcmi-blue-500 focus:border-transparent">
                            </div>
                            <div class="flex gap-2">
                                <input type="number" 
                                       :name="'next_24h_schedule[' + index + '][estimated_duration_h]'"
                                       x-model="item.estimated_duration_h"
                                       placeholder="Duración (h)"
                                       step="0.5"
                                       min="0"
                                       max="48"
                                       required
                                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sgcmi-blue-500 focus:border-transparent">
                                <button type="button" 
                                        @click="removeScheduleItem(index)"
                                        class="text-red-600 hover:text-red-800 px-2">
                                    ✕
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
                @error('next_24h_schedule')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Form Actions -->
            <div class="flex justify-end space-x-3 pt-6 border-t">
                <a href="{{ route('operations-meeting.show', $operationsMeeting) }}" class="btn-secondary">
                    Cancelar
                </a>
                <button type="submit" class="btn-primary">
                    Actualizar Junta de Operaciones
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function meetingForm() {
    return {
        attendees: @json(old('attendees', $operationsMeeting->attendees)),
        scheduleItems: @json(old('next_24h_schedule', $operationsMeeting->next_24h_schedule)),
        
        addAttendee() {
            this.attendees.push({ name: '', role: '' });
        },
        
        removeAttendee(index) {
            if (this.attendees.length > 1) {
                this.attendees.splice(index, 1);
            }
        },
        
        addScheduleItem() {
            this.scheduleItems.push({ vessel: '', operation: '', start_time: '', estimated_duration_h: '' });
        },
        
        removeScheduleItem(index) {
            if (this.scheduleItems.length > 1) {
                this.scheduleItems.splice(index, 1);
            }
        }
    }
}
</script>
@endsection
