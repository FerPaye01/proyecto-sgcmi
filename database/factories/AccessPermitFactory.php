<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AccessPermit;
use App\Models\CargoItem;
use App\Models\DigitalPass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccessPermitFactory extends Factory
{
    protected $model = AccessPermit::class;

    public function definition(): array
    {
        $authorizedAt = fake()->boolean(80) ? fake()->dateTimeBetween('-1 week', 'now') : null;
        $usedAt = $authorizedAt && fake()->boolean(40) ? fake()->dateTimeBetween($authorizedAt, 'now') : null;

        $status = 'PENDIENTE';
        if ($usedAt) {
            $status = 'USADO';
        } elseif ($authorizedAt && fake()->boolean(10)) {
            $status = 'VENCIDO';
        }

        return [
            'digital_pass_id' => DigitalPass::factory(),
            'permit_type' => fake()->randomElement(['SALIDA', 'INGRESO']),
            'cargo_item_id' => fake()->boolean(70) ? CargoItem::factory() : null,
            'authorized_by' => $authorizedAt ? User::factory() : null,
            'authorized_at' => $authorizedAt,
            'used_at' => $usedAt,
            'status' => $status,
        ];
    }

    /**
     * Indicate that the permit is pending
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'PENDIENTE',
            'authorized_at' => now()->subHours(2),
            'used_at' => null,
        ]);
    }

    /**
     * Indicate that the permit has been used
     */
    public function used(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'USADO',
            'authorized_at' => now()->subHours(4),
            'used_at' => now()->subHours(1),
        ]);
    }

    /**
     * Indicate that the permit is expired
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'VENCIDO',
            'authorized_at' => now()->subDays(7),
            'used_at' => null,
        ]);
    }

    /**
     * Indicate that the permit is for exit
     */
    public function exit(): static
    {
        return $this->state(fn (array $attributes) => [
            'permit_type' => 'SALIDA',
        ]);
    }

    /**
     * Indicate that the permit is for entry
     */
    public function entry(): static
    {
        return $this->state(fn (array $attributes) => [
            'permit_type' => 'INGRESO',
        ]);
    }
}
