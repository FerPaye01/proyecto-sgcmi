<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\VesselCall;
use Illuminate\Database\Eloquent\Factories\Factory;

class CargoManifestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vessel_call_id' => VesselCall::factory(),
            'manifest_number' => 'MAN-' . fake()->year() . '-' . fake()->unique()->numerify('####'),
            'manifest_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'total_items' => fake()->numberBetween(10, 500),
            'total_weight_kg' => fake()->randomFloat(2, 10000, 500000),
            'document_url' => fake()->boolean(70) ? fake()->url() . '/manifest.pdf' : null,
        ];
    }
}
