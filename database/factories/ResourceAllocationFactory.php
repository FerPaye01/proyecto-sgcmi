<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\VesselCall;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResourceAllocationFactory extends Factory
{
    public function definition(): array
    {
        $resourceType = fake()->randomElement(['EQUIPO', 'CUADRILLA', 'GAVIERO']);
        
        $resourceNames = [
            'EQUIPO' => ['Grúa Pórtico 1', 'Grúa Pórtico 2', 'Reach Stacker A', 'Montacargas B', 'Tractor C'],
            'CUADRILLA' => ['Cuadrilla A', 'Cuadrilla B', 'Cuadrilla C', 'Cuadrilla Nocturna'],
            'GAVIERO' => ['Gaviero Principal', 'Gaviero Auxiliar', 'Gaviero Turno Tarde'],
        ];

        return [
            'vessel_call_id' => VesselCall::factory(),
            'resource_type' => $resourceType,
            'resource_name' => fake()->randomElement($resourceNames[$resourceType]),
            'quantity' => fake()->numberBetween(1, 5),
            'shift' => fake()->randomElement(['MAÑANA', 'TARDE', 'NOCHE']),
            'allocated_at' => fake()->dateTimeBetween('-7 days', '+7 days'),
            'created_by' => User::factory(),
        ];
    }
}
