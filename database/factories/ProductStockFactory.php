<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductStock>
 */
class ProductStockFactory extends Factory
{
    protected $model = ProductStock::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantityOnHand = fake()->randomFloat(2, 0, 1000);
        $quantityReserved = fake()->randomFloat(2, 0, min($quantityOnHand, 100));

        return [
            'product_id' => Product::factory(),
            'branch_id' => Branch::factory(),
            'quantity_on_hand' => $quantityOnHand,
            'quantity_reserved' => $quantityReserved,
            'average_cost' => fake()->randomFloat(2, 10, 500),
        ];
    }
}
