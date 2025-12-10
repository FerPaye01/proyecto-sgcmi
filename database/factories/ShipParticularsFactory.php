<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\VesselCall;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipParticularsFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vessel_call_id' => VesselCall::factory(),
            'loa' => fake()->randomFloat(2, 100, 400), // Length Overall: 100-400 metros
            'beam' => fake()->randomFloat(2, 20, 60), // Manga: 20-60 metros
            'draft' => fake()->randomFloat(2, 8, 18), // Calado: 8-18 metros
            'grt' => fake()->randomFloat(2, 5000, 100000), // Gross Register Tonnage
            'nrt' => fake()->randomFloat(2, 3000, 70000), // Net Register Tonnage
            'dwt' => fake()->randomFloat(2, 10000, 200000), // Deadweight Tonnage
            'ballast_report' => [
                'total_ballast_mt' => fake()->randomFloat(2, 1000, 50000),
                'tanks' => [
                    ['tank_id' => 'FP', 'volume_m3' => fake()->randomFloat(2, 100, 5000)],
                    ['tank_id' => 'AP', 'volume_m3' => fake()->randomFloat(2, 100, 5000)],
                ],
            ],
            'dangerous_cargo' => fake()->boolean(30) ? [
                'has_dangerous_cargo' => true,
                'imdg_classes' => fake()->randomElements(['1', '2', '3', '4', '5', '6', '7', '8', '9'], fake()->numberBetween(1, 3)),
                'total_weight_kg' => fake()->randomFloat(2, 100, 10000),
            ] : null,
        ];
    }
}
