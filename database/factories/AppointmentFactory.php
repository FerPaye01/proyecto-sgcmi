<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Truck;
use App\Models\VesselCall;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'truck_id' => Truck::factory(),
            'company_id' => Company::factory(),
            'vessel_call_id' => VesselCall::factory(),
            'hora_programada' => fake()->dateTimeBetween('+1 day', '+7 days'),
            'hora_llegada' => null,
            'estado' => 'PROGRAMADA',
            'motivo' => null,
        ];
    }
}
