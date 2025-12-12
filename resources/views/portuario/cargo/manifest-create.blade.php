@extends('layouts.app')

@section('title', 'Registrar Manifiesto de Carga')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Registrar Manifiesto de Carga</h2>
            <a href="{{ route('vessel-calls.index') }}" 
               class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded transition-colors">
                Volver
            </a>
        </div>

        <form action="{{ route('cargo.manifest.store') }}" 
              method="POST" 
              x-data="manifestForm()">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                        @foreach(\App\Models\VesselCall::with('vessel')->orderBy('eta', 'desc')->get() as $vesselCall)
                            <option value="{{ $vesselCall->id }}" {{ old('vessel_call_id') == $vesselCall->id ? 'selected' : '' }}>
                                {{ $vesselCall->vessel->name }} - {{ $vesselCall->eta->format('d/m/Y H:i') }} ({{ $vesselCall->viaje_id ?? 'Sin viaje' }})
                            </option>
                        @endforeach
                    </select>
                    @error('vessel_call_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Manifest Number -->
                <div>
                    <label for="manifest_number" class="block text-sm font-medium text-gray-700 mb-2">
                        Número de Manifiesto <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="manifest_number" 
                           id="manifest_number" 
                           required
                           value="{{ old('manifest_number') }}"
                           maxlength="50"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('manifest_number') border-red-500 @enderror"
                           placeholder="Ej: MAN-2025-001">
                    @error('manifest_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Manifest Date -->
                <div>
                    <label for="manifest_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Fecha del Manifiesto <span class="text-red-500">*</span>
                    </label>
                    <input type="date" 
                           name="manifest_date" 
                           id="manifest_date" 
                           required
                           value="{{ old('manifest_date', date('Y-m-d')) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('manifest_date') border-red-500 @enderror">
                    @error('manifest_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Total Items -->
                <div>
                    <label for="total_items" class="block text-sm font-medium text-gray-700 mb-2">
                        Total de Ítems <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           name="total_items" 
                           id="total_items" 
                           required
                           min="0"
                           value="{{ old('total_items', 0) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('total_items') border-red-500 @enderror"
                           placeholder="0">
                    @error('total_items')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Total Weight -->
                <div>
                    <label for="total_weight_kg" class="block text-sm font-medium text-gray-700 mb-2">
                        Peso Total (kg) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           name="total_weight_kg" 
                           id="total_weight_kg" 
                           required
                           min="0"
                           step="0.01"
                           value="{{ old('total_weight_kg', 0) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('total_weight_kg') border-red-500 @enderror"
                           placeholder="0.00">
                    @error('total_weight_kg')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Document URL -->
                <div class="col-span-2">
                    <label for="document_url" class="block text-sm font-medium text-gray-700 mb-2">
                        URL del Documento
                    </label>
                    <input type="url" 
                           name="document_url" 
                           id="document_url" 
                           value="{{ old('document_url') }}"
                           maxlength="500"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('document_url') border-red-500 @enderror"
                           placeholder="https://ejemplo.com/documentos/manifiesto.pdf">
                    @error('document_url')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">URL del documento digitalizado (B/L, Nota de Embarque, Guía, DAM)</p>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-4 mt-6 pt-6 border-t border-gray-200">
                <a href="{{ route('vessel-calls.index') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancelar
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                    Registrar Manifiesto
                </button>
            </div>
        </form>
    </div>

    <!-- Help Section -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 class="font-semibold text-blue-900 mb-2">Información</h3>
        <ul class="text-sm text-blue-800 space-y-1">
            <li><strong>Manifiesto de Carga:</strong> Documento que lista toda la carga transportada por la nave</li>
            <li><strong>Documentos asociados:</strong> B/L (Conocimiento de Embarque), Nota de Embarque, Guía de Remisión, DAM</li>
            <li>Después de registrar el manifiesto, podrá agregar los ítems de carga individuales</li>
        </ul>
    </div>
</div>

<script>
function manifestForm() {
    return {
        // Add any client-side validation or dynamic behavior here
    };
}
</script>
@endsection
