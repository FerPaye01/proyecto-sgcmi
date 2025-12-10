<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'truck_id' => ['required', 'integer', 'exists:App\Models\Truck,id'],
            'company_id' => ['required', 'integer', 'exists:App\Models\Company,id'],
            'vessel_call_id' => ['nullable', 'integer', 'exists:App\Models\VesselCall,id'],
            'hora_programada' => ['required', 'date'],
            'hora_llegada' => ['nullable', 'date'],
            'estado' => ['required', 'in:PROGRAMADA,CONFIRMADA,ATENDIDA,NO_SHOW,CANCELADA'],
            'motivo' => ['nullable', 'string'],
        ];
    }
}
