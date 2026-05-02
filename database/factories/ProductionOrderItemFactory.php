<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderItem;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductionOrderItem>
 */
class ProductionOrderItemFactory extends Factory
{
    protected $model = ProductionOrderItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantityPlanned = fake()->randomFloat(2, 1, 100);
        $quantityUsed = fake()->randomFloat(4, 1, $quantityPlanned);
        $unitCost = fake()->randomFloat(2, 10, 500);
        $cost = round($quantityUsed * $unitCost, 2);

        return [
            'production_order_id' => ProductionOrder::factory(),
            'product_id' => Product::factory()->rawMaterial(),
            'quantity_planned' => $quantityPlanned,
            'unit_id' => Unit::factory(),
            'quantity_used' => $quantityUsed,
            'unit_cost' => $unitCost,
            'cost' => $cost,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
