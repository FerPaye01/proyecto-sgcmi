<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gate_id' => ['required', 'integer', 'exists:App\Models\Gate,id'],
            'truck_id' => ['required', 'integer', 'exists:App\Models\Truck,id'],
            'action' => ['required', 'in:ENTRADA,SALIDA'],
            'event_ts' => ['required', 'date'],
            'cita_id' => ['nullable', 'integer', 'exists:App\Models\Appointment,id'],
            'extra' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'gate_id.required' => 'El gate es obligatorio',
            'gate_id.exists' => 'El gate seleccionado no existe',
            'truck_id.required' => 'El camión es obligatorio',
            'truck_id.exists' => 'El camión seleccionado no existe',
            'action.required' => 'La acción es obligatoria',
            'action.in' => 'La acción debe ser ENTRADA o SALIDA',
            'event_ts.required' => 'La fecha y hora del evento es obligatoria',
            'event_ts.date' => 'La fecha y hora del evento debe ser una fecha válida',
            'cita_id.exists' => 'La cita seleccionada no existe',
        ];
    }
}
