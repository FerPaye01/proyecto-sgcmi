<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BerthFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'M' . fake()->unique()->numberBetween(1, 99),
            'name' => 'Muelle ' . fake()->numberBetween(1, 99),
            'capacity_teorica' => fake()->numberBetween(30000, 80000),
            'active' => true,
        ];
    }
}
