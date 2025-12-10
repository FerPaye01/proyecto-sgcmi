<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class TruckFactory extends Factory
{
    public function definition(): array
    {
        $letters = strtoupper(fake()->lexify('???'));
        $numbers = fake()->numerify('###');
        
        return [
            'placa' => $letters . '-' . $numbers,
            'company_id' => Company::factory(),
            'activo' => true,
        ];
    }
}
