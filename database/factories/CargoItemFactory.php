<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CargoManifest;
use App\Models\YardLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

class CargoItemFactory extends Factory
{
    public function definition(): array
    {
        $cargoType = fake()->randomElement(['CONTENEDOR', 'GRANEL', 'CARGA_GENERAL']);
        
        return [
            'manifest_id' => CargoManifest::factory(),
            'item_number' => fake()->unique()->numerify('ITEM-####'),
            'description' => fake()->sentence(6),
            'cargo_type' => $cargoType,
            'container_number' => $cargoType === 'CONTENEDOR' ? $this->generateContainerNumber() : null,
            'seal_number' => $cargoType === 'CONTENEDOR' ? fake()->bothify('SEAL-####??') : null,
            'weight_kg' => fake()->randomFloat(2, 100, 30000),
            'volume_m3' => fake()->randomFloat(2, 1, 100),
            'bl_number' => 'BL-' . fake()->numerify('########'),
            'consignee' => fake()->company(),
            'yard_location_id' => fake()->boolean(70) ? YardLocation::factory() : null,
            'status' => fake()->randomElement(['EN_TRANSITO', 'ALMACENADO', 'DESPACHADO']),
        ];
    }

    /**
     * Generate a valid ISO 6346 container number
     */
    private function generateContainerNumber(): string
    {
        $ownerCode = fake()->randomElement(['MSCU', 'MAEU', 'CMAU', 'CSQU', 'HLCU']);
        $serialNumber = fake()->numerify('######');
        
        // Calculate check digit (simplified)
        $checkDigit = fake()->numberBetween(0, 9);
        
        return $ownerCode . $serialNumber . $checkDigit;
    }

    /**
     * Indicate that the cargo is a container
     */
    public function container(): static
    {
        return $this->state(fn (array $attributes) => [
            'cargo_type' => 'CONTENEDOR',
            'container_number' => $this->generateContainerNumber(),
            'seal_number' => fake()->bothify('SEAL-####??'),
        ]);
    }

    /**
     * Indicate that the cargo is stored in yard
     */
    public function stored(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ALMACENADO',
            'yard_location_id' => YardLocation::factory(),
        ]);
    }
}
