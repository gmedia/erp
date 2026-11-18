<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\VehicleDriver;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleDriver>
 */
class VehicleDriverFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'license_number' => $this->faker->optional()->numerify('SIM-#######'),
            'license_type' => $this->faker->optional()->randomElement(['A', 'B', 'C']),
            'license_expiry' => $this->faker->optional()->date('Y-m-d', '+5 years'),
            'is_active' => true,
        ];
    }
}
