<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SlaDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SlaDefinition>
 */
class SlaDefinitionFactory extends Factory
{
    protected $model = SlaDefinition::class;

    public function definition(): array
    {
        $codes = [
            'TURNAROUND_48H',
            'ESPERA_CAMION_2H',
            'TRAMITE_DESPACHO_24H',
        ];

        $code = $this->faker->randomElement($codes);

        $names = [
            'TURNAROUND_48H' => 'Turnaround < 48 horas',
            'ESPERA_CAMION_2H' => 'Espera de Camión < 2 horas',
            'TRAMITE_DESPACHO_24H' => 'Despacho de Trámite < 24 horas',
        ];

        $umbrales = [
            'TURNAROUND_48H' => 48.0,
            'ESPERA_CAMION_2H' => 2.0,
            'TRAMITE_DESPACHO_24H' => 24.0,
        ];

        return [
            'code' => $code,
            'name' => $names[$code],
            'umbral' => $umbrales[$code],
            'comparador' => '<',
        ];
    }
}
