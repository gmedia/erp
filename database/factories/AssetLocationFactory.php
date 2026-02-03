<?php

namespace Database\Factories;

use App\Models\AssetLocation;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetLocationFactory extends Factory
{
    protected $model = AssetLocation::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'parent_id' => null,
            'code' => $this->faker->unique()->bothify('LOC-###'),
            'name' => $this->faker->words(2, true),
        ];
    }
}
