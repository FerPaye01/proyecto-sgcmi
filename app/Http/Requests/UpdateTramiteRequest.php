<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTramiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tramiteId = $this->route('tramite')->id ?? null;

        return [
            'tramite_ext_id' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('App\Models\Tramite', 'tramite_ext_id')->ignore($tramiteId)
            ],
            'vessel_call_id' => ['sometimes', 'required', 'integer', 'exists:App\Models\VesselCall,id'],
            'regimen' => ['sometimes', 'required', 'string', 'in:IMPORTACION,EXPORTACION,TRANSITO'],
            'subpartida' => ['nullable', 'string', 'max:20'],
            'estado' => ['sometimes', 'required', 'string', 'in:INICIADO,EN_REVISION,OBSERVADO,APROBADO,RECHAZADO'],
            'fecha_inicio' => ['sometimes', 'required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'entidad_id' => ['sometimes', 'required', 'integer', 'exists:App\Models\Entidad,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'tramite_ext_id.required' => 'El ID externo del trámite es obligatorio',
            'tramite_ext_id.unique' => 'Este ID de trámite ya existe en el sistema',
            'vessel_call_id.required' => 'La llamada de nave es obligatoria',
            'vessel_call_id.exists' => 'La llamada de nave seleccionada no existe',
            'regimen.required' => 'El régimen es obligatorio',
            'regimen.in' => 'El régimen debe ser IMPORTACION, EXPORTACION o TRANSITO',
            'estado.required' => 'El estado es obligatorio',
            'estado.in' => 'El estado debe ser INICIADO, EN_REVISION, OBSERVADO, APROBADO o RECHAZADO',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio',
            'entidad_id.required' => 'La entidad aduanera es obligatoria',
            'entidad_id.exists' => 'La entidad aduanera seleccionada no existe',
        ];
    }
}
