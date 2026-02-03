<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetMovementFactory extends Factory
{
    protected $model = AssetMovement::class;

    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'movement_type' => $this->faker->randomElement(['acquired', 'transfer', 'assign', 'return', 'dispose', 'adjustment']),
            'moved_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'from_branch_id' => null,
            'to_branch_id' => null,
            'from_location_id' => null,
            'to_location_id' => null,
            'from_department_id' => null,
            'to_department_id' => null,
            'from_employee_id' => null,
            'to_employee_id' => null,
            'reference' => $this->faker->optional()->bothify('MOV-#####'),
            'notes' => $this->faker->optional()->sentence(),
            'created_by' => $this->faker->boolean(70) ? User::factory() : null,
        ];
    }
}
