<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\User;
use App\Models\VehicleDriver;
use App\Models\VehicleLoan;
use App\Models\VehicleUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleLoan>
 */
class VehicleLoanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('+1 day', '+7 days');

        return [
            'code' => 'VL-' . $start->format('Ymd') . '-' . $this->faker->unique()->numerify('###'),
            'requester_id' => User::factory(),
            'department_id' => Department::factory(),
            'branch_id' => null,
            'company_id' => null,
            'start_at' => $start,
            'end_at' => (clone $start)->modify('+8 hours'),
            'purpose' => $this->faker->sentence(8),
            'driver_id' => VehicleDriver::factory(),
            'vehicle_unit_id' => VehicleUnit::factory(),
            'status' => 'draft',
            'submitted_at' => null,
            'approved_at' => null,
            'approved_by' => null,
            'rejection_reason' => null,
            'review_notes' => null,
        ];
    }
}
