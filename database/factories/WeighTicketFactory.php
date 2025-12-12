<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CargoItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class WeighTicketFactory extends Factory
{
    public function definition(): array
    {
        $grossWeight = fake()->randomFloat(2, 5000, 30000);
        $tareWeight = fake()->randomFloat(2, 2000, 5000);
        $netWeight = $grossWeight - $tareWeight;
        
        return [
            'cargo_item_id' => CargoItem::factory(),
            'ticket_number' => 'WEIGH-' . fake()->year() . '-' . fake()->unique()->numerify('####'),
            'weigh_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'gross_weight_kg' => $grossWeight,
            'tare_weight_kg' => $tareWeight,
            'net_weight_kg' => $netWeight,
            'scale_id' => fake()->randomElement(['SCALE-01', 'SCALE-02', 'SCALE-03']),
            'operator_name' => fake()->name(),
        ];
    }

    /**
     * Create a weigh ticket with specific weights
     */
    public function withWeights(float $gross, float $tare): static
    {
        return $this->state(fn (array $attributes) => [
            'gross_weight_kg' => $gross,
            'tare_weight_kg' => $tare,
            'net_weight_kg' => $gross - $tare,
        ]);
    }
}
