<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OperationsMeetingFactory extends Factory
{
    public function definition(): array
    {
        $meetingDate = fake()->dateTimeBetween('-30 days', '+30 days');
        
        return [
            'meeting_date' => $meetingDate,
            'meeting_time' => fake()->time('H:i:s'),
            'attendees' => [
                ['name' => fake()->name(), 'role' => 'Jefe de Operaciones'],
                ['name' => fake()->name(), 'role' => 'Supervisor de Muelle'],
                ['name' => fake()->name(), 'role' => 'Coordinador de Recursos'],
                ['name' => fake()->name(), 'role' => 'Representante Agencia Marítima'],
            ],
            'agreements' => fake()->paragraph(3),
            'next_24h_schedule' => [
                [
                    'vessel' => 'MV ' . fake()->word() . ' ' . fake()->numberBetween(100, 999),
                    'operation' => fake()->randomElement(['DESCARGA', 'CARGA', 'REESTIBA']),
                    'start_time' => fake()->time('H:i'),
                    'estimated_duration_h' => fake()->randomFloat(1, 4, 12),
                ],
                [
                    'vessel' => 'MV ' . fake()->word() . ' ' . fake()->numberBetween(100, 999),
                    'operation' => fake()->randomElement(['DESCARGA', 'CARGA']),
                    'start_time' => fake()->time('H:i'),
                    'estimated_duration_h' => fake()->randomFloat(1, 6, 18),
                ],
            ],
            'created_by' => User::factory(),
        ];
    }
}
