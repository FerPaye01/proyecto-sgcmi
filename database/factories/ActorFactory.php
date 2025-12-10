<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Actor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Actor>
 */
class ActorFactory extends Factory
{
    protected $model = Actor::class;

    public function definition(): array
    {
        $tipo = $this->faker->randomElement(['TRANSPORTISTA', 'ENTIDAD_ADUANA']);

        return [
            'ref_table' => $tipo === 'TRANSPORTISTA' ? 'terrestre.company' : 'aduanas.entidad',
            'ref_id' => $this->faker->numberBetween(1, 100),
            'tipo' => $tipo,
            'name' => $this->faker->company(),
        ];
    }
}
