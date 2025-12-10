<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Berth;
use App\Models\Vessel;
use Illuminate\Database\Eloquent\Factories\Factory;

class VesselCallFactory extends Factory
{
    public function definition(): array
    {
        $eta = fake()->dateTimeBetween('+1 day', '+30 days');
        $etb = (clone $eta)->modify('+2 hours');
        
        return [
            'vessel_id' => Vessel::factory(),
            'viaje_id' => 'V' . fake()->year() . fake()->unique()->numerify('###'),
            'berth_id' => Berth::factory(),
            'eta' => $eta,
            'etb' => $etb,
            'ata' => null,
            'atb' => null,
            'atd' => null,
            'estado_llamada' => fake()->randomElement(['PROGRAMADA', 'EN_TRANSITO', 'ATRACADA', 'OPERANDO', 'ZARPO']),
            'motivo_demora' => null,
        ];
    }
}
