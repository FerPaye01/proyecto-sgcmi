@extends('layouts.app')

@section('title', 'New Tarja Note')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">New Tarja Note</h1>
        <p class="mt-2 text-sm text-gray-600">Register physical verification of cargo</p>
    </div>

    @if($errors->any())
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Error!</strong>
            <ul class="mt-2 list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white shadow-md rounded-lg p-6">
        <form method="POST" action="{{ route('tarja.store') }}" x-data="tarjaForm()">
            @csrf

            <!-- Cargo Item Selection -->
            <div class="mb-6">
                <label for="cargo_item_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Cargo Item <span class="text-red-500">*</span>
                </label>
                <select name="cargo_item_id" id="cargo_item_id" required
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        x-model="cargoItemId"
                        @change="updateCargoInfo()">
                    <option value="">Select a cargo item</option>
                    @foreach($cargoItems as $item)
                        <option value="{{ $item->id }}" 
                                {{ old('cargo_item_id', $cargoItem?->id) == $item->id ? 'selected' : '' }}
                                data-item-number="{{ $item->item_number }}"
                                data-description="{{ $item->description }}"
                                data-container="{{ $item->container_number }}"
                                data-vessel="{{ $item->manifest->vesselCall->vessel->name ?? 'N/A' }}">
                            {{ $item->item_number }} - {{ $item->description }} 
                            ({{ $item->manifest->vesselCall->vessel->name ?? 'N/A' }})
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-sm text-gray-500">Select the cargo item to inspect</p>
            </div>

            <!-- Cargo Item Info Display -->
            @if($cargoItem)
            <div class="mb-6 p-4 bg-blue-50 rounded-md">
                <h3 class="text-sm font-medium text-blue-900 mb-2">Cargo Item Information</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600">Item Number:</span>
                        <span class="font-medium">{{ $cargoItem->item_number }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Container:</span>
                        <span class="font-medium">{{ $cargoItem->container_number ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Vessel:</span>
                        <span class="font-medium">{{ $cargoItem->manifest->vesselCall->vessel->name ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Weight:</span>
                        <span class="font-medium">{{ number_format($cargoItem->weight_kg, 2) }} kg</span>
                    </div>
                </div>
            </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Tarja Number -->
                <div>
                    <label for="tarja_number" class="block text-sm font-medium text-gray-700 mb-2">
                        Tarja Number <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="tarja_number" id="tarja_number" required
                           value="{{ old('tarja_number', 'TARJA-' . date('Y') . '-') }}"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                           placeholder="TARJA-2024-0001">
                    <p class="mt-1 text-sm text-gray-500">Unique tarja identification number</p>
                </div>

                <!-- Tarja Date -->
                <div>
                    <label for="tarja_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Tarja Date <span class="text-red-500">*</span>
                    </label>
                    <input type="datetime-local" name="tarja_date" id="tarja_date" required
                           value="{{ old('tarja_date', now()->format('Y-m-d\TH:i')) }}"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <p class="mt-1 text-sm text-gray-500">Date and time of inspection</p>
                </div>

                <!-- Inspector Name -->
                <div>
                    <label for="inspector_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Inspector Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="inspector_name" id="inspector_name" required
                           value="{{ old('inspector_name', auth()->user()->name) }}"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                           placeholder="Inspector name">
                    <p class="mt-1 text-sm text-gray-500">Name of the person performing the inspection</p>
                </div>

                <!-- Condition -->
                <div>
                    <label for="condition" class="block text-sm font-medium text-gray-700 mb-2">
                        Condition <span class="text-red-500">*</span>
                    </label>
                    <select name="condition" id="condition" required
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Select condition</option>
                        <option value="BUENO" {{ old('condition') == 'BUENO' ? 'selected' : '' }}>BUENO (Good)</option>
                        <option value="DAÑADO" {{ old('condition') == 'DAÑADO' ? 'selected' : '' }}>DAÑADO (Damaged)</option>
                        <option value="FALTANTE" {{ old('condition') == 'FALTANTE' ? 'selected' : '' }}>FALTANTE (Missing)</option>
                    </select>
                    <p class="mt-1 text-sm text-gray-500">Physical condition of the cargo</p>
                </div>
            </div>

            <!-- Observations -->
            <div class="mt-6">
                <label for="observations" class="block text-sm font-medium text-gray-700 mb-2">
                    Observations
                </label>
                <textarea name="observations" id="observations" rows="4"
                          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                          placeholder="Ingrese observaciones sobre la condición de la carga">{{ old('observations') }}</textarea>
                <p class="mt-1 text-sm text-gray-500">Observaciones detalladas sobre la carga (opcional)</p>
            </div>

            <!-- Photos -->
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Photos
                </label>
                <div x-data="{ photos: [] }">
                    <div class="mb-2">
                        <button type="button" @click="photos.push('')" 
                                class="px-3 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                            + Add Photo URL
                        </button>
                    </div>
                    <template x-for="(photo, index) in photos" :key="index">
                        <div class="flex gap-2 mb-2">
                            <input type="text" :name="'photos[' + index + ']'" x-model="photos[index]"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   placeholder="https://example.com/photo.jpg">
                            <button type="button" @click="photos.splice(index, 1)"
                                    class="px-3 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-700">
                                Remove
                            </button>
                        </div>
                    </template>
                </div>
                <p class="mt-1 text-sm text-gray-500">Add URLs of photos documenting the cargo condition (optional)</p>
            </div>

            <!-- Form Actions -->
            <div class="mt-8 flex justify-end space-x-3">
                <a href="{{ route('tarja.index') }}" 
                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                    Register Tarja Note
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function tarjaForm() {
    return {
        cargoItemId: '{{ old('cargo_item_id', $cargoItem?->id) }}',
        updateCargoInfo() {
            // This could be enhanced to dynamically load cargo info via AJAX
            console.log('Cargo item selected:', this.cargoItemId);
        }
    }
}
</script>
@endsection
