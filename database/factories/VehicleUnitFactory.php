<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\VehicleUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleUnit>
 */
class VehicleUnitFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'plate_number' => strtoupper($this->faker->unique()->bothify('?? #### ???')),
            'vehicle_type' => $this->faker->randomElement(['car', 'motorcycle']),
            'capacity' => $this->faker->optional()->numberBetween(2, 8),
            'is_active' => true,
        ];
    }
}
