<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VehicleLoan;
use App\Models\VehicleLoanPickup;
use App\Models\VehicleLoanReturn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleLoanReturn>
 */
class VehicleLoanReturnFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pickup_id' => VehicleLoanPickup::factory(),
            'vehicle_loan_id' => VehicleLoan::factory(),
            'returner_id' => User::factory(),
            'handover_photo_path' => 'vehicle-loans/returns/' . $this->faker->uuid() . '.jpg',
            'handover_photo_name' => 'handover.jpg',
            'condition_photo_path' => 'vehicle-loans/returns/' . $this->faker->uuid() . '.jpg',
            'condition_photo_name' => 'condition.jpg',
            'incident' => false,
            'incident_note' => null,
            'odometer_start' => $this->faker->numberBetween(1000, 90000),
            'odometer_end' => null,
            'returned_at' => now(),
            'received_by_security_id' => null,
        ];
    }
}
