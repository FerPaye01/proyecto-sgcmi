<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Gate;
use App\Models\GateEvent;
use App\Models\Truck;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GateEvent>
 */
class GateEventFactory extends Factory
{
    protected $model = GateEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gate_id' => Gate::factory(),
            'truck_id' => Truck::factory(),
            'action' => $this->faker->randomElement(['ENTRADA', 'SALIDA']),
            'event_ts' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'cita_id' => null,
            'extra' => $this->faker->boolean(20) ? ['nota' => $this->faker->sentence()] : null,
        ];
    }

    /**
     * Indicate that the gate event is linked to an appointment.
     */
    public function withAppointment(): static
    {
        return $this->state(fn (array $attributes) => [
            'cita_id' => Appointment::factory(),
        ]);
    }

    /**
     * Indicate that the gate event is an entrance.
     */
    public function entrance(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'ENTRADA',
        ]);
    }

    /**
     * Indicate that the gate event is an exit.
     */
    public function exit(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'SALIDA',
        ]);
    }
}
