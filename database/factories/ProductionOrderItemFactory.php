<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductionOrderItem>
 */
class ProductionOrderItemFactory extends Factory
{
    protected $model = ProductionOrderItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantityUsed = fake()->randomFloat(4, 1, 100);
        $unitCost = fake()->randomFloat(2, 10, 500);
        $totalCost = $quantityUsed * $unitCost;

        return [
            'production_order_id' => ProductionOrder::factory(),
            'raw_material_id' => Product::factory()->rawMaterial(),
            'quantity_used' => $quantityUsed,
            'unit_cost' => $unitCost,
            'total_cost' => round($totalCost, 2),
        ];
    }
}
