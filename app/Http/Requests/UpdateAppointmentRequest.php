<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'truck_id' => ['sometimes', 'integer', 'exists:App\Models\Truck,id'],
            'company_id' => ['sometimes', 'integer', 'exists:App\Models\Company,id'],
            'vessel_call_id' => ['nullable', 'integer', 'exists:App\Models\VesselCall,id'],
            'hora_programada' => ['sometimes', 'date'],
            'hora_llegada' => ['nullable', 'date'],
            'estado' => ['sometimes', 'in:PROGRAMADA,CONFIRMADA,ATENDIDA,NO_SHOW,CANCELADA'],
            'motivo' => ['nullable', 'string'],
        ];
    }
}
