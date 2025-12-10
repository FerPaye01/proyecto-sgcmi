<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->word()) . '_' . fake()->randomElement(['READ', 'WRITE', 'ADMIN']),
            'name' => fake()->sentence(3),
        ];
    }
}
