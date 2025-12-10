<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tramite;
use App\Models\VesselCall;
use App\Models\Entidad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tramite>
 */
class TramiteFactory extends Factory
{
    protected $model = Tramite::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $estados = ['INICIADO', 'EN_REVISION', 'OBSERVADO', 'APROBADO', 'RECHAZADO'];
        $regimenes = ['IMPORTACION', 'EXPORTACION', 'TRANSITO'];
        
        $estado = $this->faker->randomElement($estados);
        $fechaInicio = $this->faker->dateTimeBetween('-30 days', 'now');
        
        // Si está aprobado o rechazado, tiene fecha_fin
        $fechaFin = in_array($estado, ['APROBADO', 'RECHAZADO']) 
            ? $this->faker->dateTimeBetween($fechaInicio, 'now')
            : null;

        return [
            'tramite_ext_id' => strtoupper($this->faker->unique()->bothify('TRM-####-????')),
            'vessel_call_id' => VesselCall::factory(),
            'regimen' => $this->faker->randomElement($regimenes),
            'subpartida' => $this->faker->numerify('####.##.##'),
            'estado' => $estado,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'entidad_id' => Entidad::factory(),
        ];
    }
}
