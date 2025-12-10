<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\VesselCall;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoadingPlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vessel_call_id' => VesselCall::factory(),
            'operation_type' => fake()->randomElement(['CARGA', 'DESCARGA', 'REESTIBA']),
            'sequence_order' => fake()->numberBetween(1, 10),
            'estimated_duration_h' => fake()->randomFloat(2, 2, 24),
            'equipment_required' => [
                'cranes' => fake()->numberBetween(1, 4),
                'forklifts' => fake()->numberBetween(0, 5),
                'reach_stackers' => fake()->numberBetween(0, 3),
            ],
            'crew_required' => fake()->numberBetween(5, 30),
            'status' => fake()->randomElement(['PLANIFICADO', 'EN_EJECUCION', 'COMPLETADO']),
        ];
    }
}
