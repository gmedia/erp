<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VehicleLoan;
use App\Models\VehicleLoanPickup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleLoanPickup>
 */
class VehicleLoanPickupFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vehicle_loan_id' => VehicleLoan::factory(),
            'requester_id' => User::factory(),
            'photo_mode' => 'with_driver',
            'selfie_photo_path' => 'vehicle-loans/pickups/' . $this->faker->uuid() . '.jpg',
            'selfie_photo_name' => 'selfie.jpg',
            'driver_snapshot_id' => null,
            'unit_snapshot_id' => null,
            'picked_at' => now(),
            'key_handover_at' => null,
            'security_user_id' => null,
        ];
    }
}
