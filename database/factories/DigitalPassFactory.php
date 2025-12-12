<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DigitalPass;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DigitalPassFactory extends Factory
{
    protected $model = DigitalPass::class;

    public function definition(): array
    {
        $validFrom = fake()->dateTimeBetween('-1 month', 'now');
        $validUntil = fake()->dateTimeBetween($validFrom, '+3 months');

        return [
            'pass_code' => 'DP-' . strtoupper(fake()->bothify('??########')),
            'qr_code' => base64_encode(fake()->text(100)), // Mock QR code
            'pass_type' => fake()->randomElement(['PERSONAL', 'VEHICULAR']),
            'holder_name' => fake()->name(),
            'holder_dni' => fake()->numerify('########'),
            'truck_id' => fake()->boolean(70) ? Truck::factory() : null,
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'status' => fake()->randomElement(['ACTIVO', 'VENCIDO', 'REVOCADO']),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the pass is active
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ACTIVO',
            'valid_from' => now()->subDays(1),
            'valid_until' => now()->addMonths(1),
        ]);
    }

    /**
     * Indicate that the pass is expired
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'VENCIDO',
            'valid_from' => now()->subMonths(2),
            'valid_until' => now()->subDays(1),
        ]);
    }

    /**
     * Indicate that the pass is revoked
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'REVOCADO',
        ]);
    }

    /**
     * Indicate that the pass is for personal access
     */
    public function personal(): static
    {
        return $this->state(fn (array $attributes) => [
            'pass_type' => 'PERSONAL',
            'truck_id' => null,
        ]);
    }

    /**
     * Indicate that the pass is for vehicular access
     */
    public function vehicular(): static
    {
        return $this->state(fn (array $attributes) => [
            'pass_type' => 'VEHICULAR',
            'truck_id' => Truck::factory(),
        ]);
    }
}
