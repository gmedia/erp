<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetMaintenance;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetMaintenanceFactory extends Factory
{
    protected $model = AssetMaintenance::class;

    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'maintenance_type' => $this->faker->randomElement(['preventive', 'corrective', 'calibration', 'other']),
            'status' => $this->faker->randomElement(['scheduled', 'in_progress', 'completed', 'cancelled']),
            'scheduled_at' => $this->faker->optional()->dateTimeBetween('-1 year', '+1 month'),
            'performed_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'supplier_id' => $this->faker->boolean(60) ? Supplier::factory() : null,
            'cost' => $this->faker->numberBetween(0, 5_000_000),
            'notes' => $this->faker->optional()->sentence(),
            'created_by' => $this->faker->boolean(70) ? User::factory() : null,
        ];
    }
}
