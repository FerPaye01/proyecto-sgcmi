@extends('layouts.app')

@section('title', 'Solicitud de Servicio de Nave')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Solicitud de Servicio de Nave</h1>
            <a href="{{ route('vessel-calls.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                Volver
            </a>
        </div>

        <form action="{{ route('vessel-planning.store-service-request') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Información Básica de la Llamada -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Información Básica</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="vessel_id" class="block text-sm font-medium text-gray-700">Nave *</label>
                        <select name="vessel_id" id="vessel_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Seleccionar nave...</option>
                            @foreach($vessels as $vessel)
                                <option value="{{ $vessel->id }}" {{ old('vessel_id') == $vessel->id ? 'selected' : '' }}>
                                    {{ $vessel->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('vessel_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="viaje_id" class="block text-sm font-medium text-gray-700">Viaje ID</label>
                        <input type="text" name="viaje_id" id="viaje_id" value="{{ old('viaje_id') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('viaje_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="berth_id" class="block text-sm font-medium text-gray-700">Muelle</label>
                        <select name="berth_id" id="berth_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Seleccionar muelle...</option>
                            @foreach($berths as $berth)
                                <option value="{{ $berth->id }}" {{ old('berth_id') == $berth->id ? 'selected' : '' }}>
                                    {{ $berth->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('berth_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="eta" class="block text-sm font-medium text-gray-700">ETA (Tiempo Estimado de Arribo) *</label>
                        <input type="datetime-local" name="eta" id="eta" value="{{ old('eta') }}" required
                               min="{{ now()->addHours(48)->format('Y-m-d\TH:i') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-500">⏰ Debe ser al menos 48 horas en el futuro (anticipación requerida)</p>
                        @error('eta')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="etb" class="block text-sm font-medium text-gray-700">ETB (Tiempo Estimado de Atraque)</label>
                        <input type="datetime-local" name="etb" id="etb" value="{{ old('etb') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('etb')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="estado_llamada" class="block text-sm font-medium text-gray-700">Estado *</label>
                        <select name="estado_llamada" id="estado_llamada" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="PROGRAMADA" {{ old('estado_llamada') == 'PROGRAMADA' ? 'selected' : '' }}>Programada</option>
                            <option value="EN_TRANSITO" {{ old('estado_llamada') == 'EN_TRANSITO' ? 'selected' : '' }}>En Tránsito</option>
                        </select>
                        @error('estado_llamada')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Ship Particulars -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Particulares de la Nave</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="loa" class="block text-sm font-medium text-gray-700">LOA (Eslora) m *</label>
                        <input type="number" step="0.01" name="loa" id="loa" value="{{ old('loa') }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('loa')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="beam" class="block text-sm font-medium text-gray-700">Manga m *</label>
                        <input type="number" step="0.01" name="beam" id="beam" value="{{ old('beam') }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('beam')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="draft" class="block text-sm font-medium text-gray-700">Calado m *</label>
                        <input type="number" step="0.01" name="draft" id="draft" value="{{ old('draft') }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('draft')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="grt" class="block text-sm font-medium text-gray-700">GRT (Tonelaje Bruto)</label>
                        <input type="number" step="0.01" name="grt" id="grt" value="{{ old('grt') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('grt')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="nrt" class="block text-sm font-medium text-gray-700">NRT (Tonelaje Neto)</label>
                        <input type="number" step="0.01" name="nrt" id="nrt" value="{{ old('nrt') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('nrt')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="dwt" class="block text-sm font-medium text-gray-700">DWT (Peso Muerto)</label>
                        <input type="number" step="0.01" name="dwt" id="dwt" value="{{ old('dwt') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('dwt')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-sm text-gray-600 italic">
                        Nota: Los reportes de lastre y mercancías peligrosas se pueden adjuntar después de crear la solicitud.
                    </p>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end space-x-3">
                <a href="{{ route('vessel-calls.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg font-medium">
                    Cancelar
                </a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                    Registrar Solicitud
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
