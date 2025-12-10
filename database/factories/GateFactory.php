<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Gate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Gate>
 */
class GateFactory extends Factory
{
    protected $model = Gate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'G' . $this->faker->unique()->numberBetween(1, 999),
            'name' => 'Gate ' . $this->faker->unique()->numberBetween(1, 999),
            'activo' => true,
        ];
    }
}
