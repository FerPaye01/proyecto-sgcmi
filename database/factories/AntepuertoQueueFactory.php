<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AntepuertoQueue;
use App\Models\Appointment;
use App\Models\Truck;
use Illuminate\Database\Eloquent\Factories\Factory;

class AntepuertoQueueFactory extends Factory
{
    protected $model = AntepuertoQueue::class;

    public function definition(): array
    {
        $entryTime = fake()->boolean(90) ? fake()->dateTimeBetween('-2 hours', 'now') : null;
        $exitTime = $entryTime && fake()->boolean(60) ? fake()->dateTimeBetween($entryTime, 'now') : null;

        $status = 'EN_ESPERA';
        if ($exitTime) {
            $status = fake()->randomElement(['AUTORIZADO', 'RECHAZADO']);
        }

        return [
            'truck_id' => Truck::factory(),
            'appointment_id' => fake()->boolean(80) ? Appointment::factory() : null,
            'entry_time' => $entryTime,
            'exit_time' => $exitTime,
            'zone' => fake()->randomElement(['ANTEPUERTO', 'ZOE']),
            'status' => $status,
        ];
    }

    /**
     * Indicate that the truck is currently in queue
     */
    public function inQueue(): static
    {
        return $this->state(fn (array $attributes) => [
            'entry_time' => now()->subMinutes(fake()->numberBetween(5, 120)),
            'exit_time' => null,
            'status' => 'EN_ESPERA',
        ]);
    }

    /**
     * Indicate that the truck was authorized
     */
    public function authorized(): static
    {
        return $this->state(fn (array $attributes) => [
            'entry_time' => now()->subHours(2),
            'exit_time' => now()->subHour(),
            'status' => 'AUTORIZADO',
        ]);
    }

    /**
     * Indicate that the truck was rejected
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'entry_time' => now()->subHours(2),
            'exit_time' => now()->subHour(),
            'status' => 'RECHAZADO',
        ]);
    }

    /**
     * Indicate that the truck is in antepuerto zone
     */
    public function antepuerto(): static
    {
        return $this->state(fn (array $attributes) => [
            'zone' => 'ANTEPUERTO',
        ]);
    }

    /**
     * Indicate that the truck is in ZOE zone
     */
    public function zoe(): static
    {
        return $this->state(fn (array $attributes) => [
            'zone' => 'ZOE',
        ]);
    }
}
