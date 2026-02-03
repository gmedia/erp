<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetDepreciationLine;
use App\Models\AssetDepreciationRun;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetDepreciationLineFactory extends Factory
{
    protected $model = AssetDepreciationLine::class;

    public function definition(): array
    {
        $amount = $this->faker->numberBetween(10_000, 10_000_000);
        $before = $this->faker->numberBetween(0, 50_000_000);
        $after = $before + $amount;

        return [
            'asset_depreciation_run_id' => AssetDepreciationRun::factory(),
            'asset_id' => Asset::factory(),
            'amount' => $amount,
            'accumulated_before' => $before,
            'accumulated_after' => $after,
            'book_value_after' => max(0, $this->faker->numberBetween(0, 200_000_000) - $after),
        ];
    }
}
