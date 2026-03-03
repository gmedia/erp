<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockAdjustmentItemFactory extends Factory
{
    protected $model = StockAdjustmentItem::class;

    public function definition(): array
    {
        $quantityBefore = $this->faker->randomFloat(2, 0, 1000);
        $quantityAdjusted = $this->faker->randomFloat(2, 0.01, 200);

        if ($this->faker->boolean(50)) {
            $quantityAdjusted = -$quantityAdjusted;
        }

        $quantityAfter = $quantityBefore + $quantityAdjusted;
        $unitCost = $this->faker->randomFloat(2, 0, 1000);
        $totalCost = abs($quantityAdjusted) * $unitCost;

        return [
            'stock_adjustment_id' => StockAdjustment::factory(),
            'product_id' => Product::factory(),
            'unit_id' => Unit::factory(),
            'quantity_before' => $quantityBefore,
            'quantity_adjusted' => $quantityAdjusted,
            'quantity_after' => $quantityAfter,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'reason' => $this->faker->optional()->sentence(),
        ];
    }
}
