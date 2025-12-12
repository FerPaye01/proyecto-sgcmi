<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class YardLocationFactory extends Factory
{
    public function definition(): array
    {
        $locationType = fake()->randomElement(['CONTENEDOR', 'SILO', 'ALMACEN', 'LOSA']);
        
        return [
            'zone_code' => fake()->randomElement(['A', 'B', 'C', 'D', 'E']),
            'block_code' => fake()->randomElement(['01', '02', '03', '04', '05']),
            'row_code' => fake()->randomElement(['R1', 'R2', 'R3', 'R4', 'R5']),
            'tier' => $locationType === 'CONTENEDOR' ? fake()->numberBetween(1, 5) : null,
            'location_type' => $locationType,
            'capacity_teu' => $locationType === 'CONTENEDOR' ? fake()->numberBetween(1, 4) : null,
            'occupied' => fake()->boolean(30),
            'active' => true,
        ];
    }

    /**
     * Indicate that the location is occupied
     */
    public function occupied(): static
    {
        return $this->state(fn (array $attributes) => [
            'occupied' => true,
        ]);
    }

    /**
     * Indicate that the location is available
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'occupied' => false,
        ]);
    }
}
