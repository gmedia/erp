<?php

namespace Database\Factories;

use App\Models\AssetCategory;
use App\Models\AssetModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetModelFactory extends Factory
{
    protected $model = AssetModel::class;

    public function definition(): array
    {
        return [
            'asset_category_id' => AssetCategory::factory(),
            'manufacturer' => $this->faker->optional()->company(),
            'model_name' => $this->faker->words(3, true),
            'specs' => [
                'cpu' => $this->faker->randomElement(['i3', 'i5', 'i7', 'Ryzen 5', 'Ryzen 7']),
                'ram_gb' => $this->faker->randomElement([8, 16, 32]),
                'storage_gb' => $this->faker->randomElement([256, 512, 1024]),
            ],
        ];
    }
}
