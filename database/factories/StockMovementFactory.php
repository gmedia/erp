<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockMovement>
 */
class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $movementType = $this->faker->randomElement([
            'goods_receipt',
            'supplier_return',
            'transfer_out',
            'transfer_in',
            'adjustment_in',
            'adjustment_out',
            'production_consume',
            'production_output',
            'sales',
            'sales_return',
        ]);

        $isIn = in_array($movementType, [
            'goods_receipt',
            'transfer_in',
            'adjustment_in',
            'production_output',
            'sales_return',
        ], true);

        $qty = $this->faker->randomFloat(2, 1, 500);
        $qtyIn = $isIn ? $qty : 0;
        $qtyOut = $isIn ? 0 : $qty;

        return [
            'product_id' => Product::factory(),
            'warehouse_id' => Warehouse::factory(),
            'movement_type' => $movementType,
            'quantity_in' => $qtyIn,
            'quantity_out' => $qtyOut,
            'balance_after' => $this->faker->randomFloat(2, 0, 5000),
            'unit_cost' => $this->faker->optional()->randomFloat(2, 1, 100000),
            'average_cost_after' => $this->faker->optional()->randomFloat(2, 1, 100000),
            'reference_type' => null,
            'reference_id' => null,
            'reference_number' => null,
            'notes' => $this->faker->optional()->sentence(),
            'moved_at' => now(),
            'created_by' => User::factory(),
        ];
    }
}

