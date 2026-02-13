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
            'from_branch_id' => \App\Models\Branch::factory(),
            'to_branch_id' => \App\Models\Branch::factory(),
            'from_location_id' => \App\Models\AssetLocation::factory(),
            'to_location_id' => \App\Models\AssetLocation::factory(),
            'from_department_id' => \App\Models\Department::factory(),
            'to_department_id' => \App\Models\Department::factory(),
            'from_employee_id' => \App\Models\Employee::factory(),
            'to_employee_id' => \App\Models\Employee::factory(),
            'created_by' => \App\Models\User::factory(),
            'reference' => $this->faker->optional()->bothify('MOV-#####'),
            'notes' => $this->faker->optional()->sentence(),
            'created_by' => $this->faker->boolean(70) ? User::factory() : null,
        ];
    }
}
