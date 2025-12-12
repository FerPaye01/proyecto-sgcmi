<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CargoItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TarjaNoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cargo_item_id' => CargoItem::factory(),
            'tarja_number' => 'TARJA-' . fake()->year() . '-' . fake()->unique()->numerify('####'),
            'tarja_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'inspector_name' => fake()->name(),
            'observations' => fake()->boolean(60) ? fake()->sentence(10) : null,
            'condition' => fake()->randomElement(['BUENO', 'DAÑADO', 'FALTANTE']),
            'photos' => fake()->boolean(40) ? [
                fake()->imageUrl(640, 480, 'cargo', true),
                fake()->imageUrl(640, 480, 'cargo', true),
            ] : null,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the cargo is in good condition
     */
    public function goodCondition(): static
    {
        return $this->state(fn (array $attributes) => [
            'condition' => 'BUENO',
            'observations' => null,
        ]);
    }

    /**
     * Indicate that the cargo is damaged
     */
    public function damaged(): static
    {
        return $this->state(fn (array $attributes) => [
            'condition' => 'DAÑADO',
            'observations' => fake()->sentence(15),
            'photos' => [
                fake()->imageUrl(640, 480, 'damage', true),
                fake()->imageUrl(640, 480, 'damage', true),
            ],
        ]);
    }
}
