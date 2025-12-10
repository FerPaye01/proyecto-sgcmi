<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ruc' => fake()->unique()->numerify('20#########'),
            'name' => fake()->company(),
            'tipo' => fake()->randomElement(['TRANSPORTISTA', 'AGENTE_ADUANA', 'OPERADOR']),
            'active' => true,
        ];
    }
}
