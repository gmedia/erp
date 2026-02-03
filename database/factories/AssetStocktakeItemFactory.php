<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetStocktake;
use App\Models\AssetStocktakeItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetStocktakeItemFactory extends Factory
{
    protected $model = AssetStocktakeItem::class;

    public function definition(): array
    {
        return [
            'asset_stocktake_id' => AssetStocktake::factory(),
            'asset_id' => Asset::factory(),
            'expected_branch_id' => null,
            'expected_location_id' => null,
            'found_branch_id' => null,
            'found_location_id' => null,
            'result' => $this->faker->randomElement(['found', 'missing', 'damaged', 'moved']),
            'notes' => $this->faker->optional()->sentence(),
            'checked_at' => $this->faker->optional()->dateTimeBetween('-6 months', 'now'),
            'checked_by' => $this->faker->boolean(70) ? User::factory() : null,
        ];
    }
}
