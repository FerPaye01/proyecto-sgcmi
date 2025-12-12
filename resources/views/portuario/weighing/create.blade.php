@extends('layouts.app')

@section('title', 'New Weigh Ticket')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">New Weigh Ticket</h1>
        <p class="text-gray-600 mt-2">Register a new weigh ticket. Net weight will be calculated automatically.</p>
    </div>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-md p-6">
        <form method="POST" action="{{ route('weighing.store') }}" x-data="weighingForm()">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Cargo Item Selection -->
                <div class="md:col-span-2">
                    <label for="cargo_item_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Cargo Item <span class="text-red-500">*</span>
                    </label>
                    <select name="cargo_item_id" id="cargo_item_id" required
                            class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select a cargo item</option>
                        @foreach($cargoItems as $item)
                            <option value="{{ $item->id }}" 
                                    {{ (old('cargo_item_id', $cargoItem?->id) == $item->id) ? 'selected' : '' }}>
                                {{ $item->item_number }} - {{ $item->description }} 
                                ({{ $item->manifest?->vesselCall?->vessel->name ?? 'N/A' }})
                            </option>
                        @endforeach
                    </select>
                    @error('cargo_item_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Ticket Number -->
                <div>
                    <label for="ticket_number" class="block text-sm font-medium text-gray-700 mb-2">
                        Ticket Number <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="ticket_number" id="ticket_number" required
                           value="{{ old('ticket_number', 'WT-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT)) }}"
                           class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    @error('ticket_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Weigh Date -->
                <div>
                    <label for="weigh_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Weigh Date <span class="text-red-500">*</span>
                    </label>
                    <input type="datetime-local" name="weigh_date" id="weigh_date" required
                           value="{{ old('weigh_date', now()->format('Y-m-d\TH:i')) }}"
                           class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    @error('weigh_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Gross Weight -->
                <div>
                    <label for="gross_weight_kg" class="block text-sm font-medium text-gray-700 mb-2">
                        Gross Weight (kg) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="gross_weight_kg" id="gross_weight_kg" required
                           step="0.01" min="0"
                           value="{{ old('gross_weight_kg') }}"
                           x-model.number="grossWeight"
                           @input="calculateNet()"
                           class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    @error('gross_weight_kg')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tare Weight -->
                <div>
                    <label for="tare_weight_kg" class="block text-sm font-medium text-gray-700 mb-2">
                        Tare Weight (kg) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="tare_weight_kg" id="tare_weight_kg" required
                           step="0.01" min="0"
                           value="{{ old('tare_weight_kg') }}"
                           x-model.number="tareWeight"
                           @input="calculateNet()"
                           class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    @error('tare_weight_kg')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Net Weight (Calculated) -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Net Weight (kg) - Calculated Automatically
                    </label>
                    <div class="w-full border-2 border-blue-300 bg-blue-50 rounded-lg px-4 py-3 text-2xl font-bold text-blue-700">
                        <span x-text="netWeight.toFixed(2)">0.00</span> kg
                    </div>
                    <p class="mt-1 text-sm text-gray-500">
                        Net Weight = Gross Weight - Tare Weight
                    </p>
                </div>

                <!-- Scale ID -->
                <div>
                    <label for="scale_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Scale ID <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="scale_id" id="scale_id" required
                           value="{{ old('scale_id') }}"
                           placeholder="e.g., SCALE-01"
                           class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    @error('scale_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Operator Name -->
                <div>
                    <label for="operator_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Operator Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="operator_name" id="operator_name" required
                           value="{{ old('operator_name', auth()->user()->name ?? '') }}"
                           class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    @error('operator_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-6 flex justify-end gap-4">
                <a href="{{ route('weighing.index') }}" 
                   class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg transition">
                    Cancel
                </a>
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition">
                    Register Weigh Ticket
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function weighingForm() {
    return {
        grossWeight: {{ old('gross_weight_kg', 0) }},
        tareWeight: {{ old('tare_weight_kg', 0) }},
        netWeight: 0,
        
        init() {
            this.calculateNet();
        },
        
        calculateNet() {
            this.netWeight = Math.max(0, this.grossWeight - this.tareWeight);
        }
    }
}
</script>
@endsection
