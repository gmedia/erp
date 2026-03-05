<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockTransferItem>
 */
class StockTransferItemFactory extends Factory
{
    protected $model = StockTransferItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stock_transfer_id' => StockTransfer::factory(),
            'product_id' => Product::factory(),
            'unit_id' => Unit::factory(),
            'quantity' => $this->faker->randomFloat(2, 1, 100),
            'quantity_received' => 0,
            'unit_cost' => $this->faker->randomFloat(2, 0, 100000),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
