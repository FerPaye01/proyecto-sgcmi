<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class VesselFactory extends Factory
{
    public function definition(): array
    {
        return [
            'imo' => 'IMO' . fake()->unique()->numerify('#######'),
            'name' => fake()->company() . ' ' . fake()->randomElement(['SHIP', 'VESSEL', 'CARRIER']),
            'flag_country' => fake()->country(),
            'type' => fake()->randomElement(['Container', 'Bulk', 'Tanker', 'RoRo']),
        ];
    }
}
