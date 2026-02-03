<?php

namespace Database\Factories;

use App\Models\AssetCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetCategoryFactory extends Factory
{
    protected $model = AssetCategory::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->bothify('AC-####'),
            'name' => $this->faker->words(3, true),
            'useful_life_months_default' => $this->faker->randomElement([12, 24, 36, 48, 60, 120]),
        ];
    }
}
