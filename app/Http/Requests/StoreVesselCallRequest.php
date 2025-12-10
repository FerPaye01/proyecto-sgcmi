<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVesselCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vessel_id' => ['required', 'integer', 'exists:App\Models\Vessel,id'],
            'viaje_id' => ['nullable', 'string', 'max:255'],
            'berth_id' => ['nullable', 'integer', 'exists:App\Models\Berth,id'],
            'eta' => ['required', 'date'],
            'etb' => ['nullable', 'date', 'after_or_equal:eta'],
            'ata' => ['nullable', 'date'],
            'atb' => ['nullable', 'date'],
            'atd' => ['nullable', 'date'],
            'estado_llamada' => ['required', 'in:PROGRAMADA,EN_TRANSITO,ATRACADA,OPERANDO,ZARPO'],
            'motivo_demora' => ['nullable', 'string'],
        ];
    }
}
