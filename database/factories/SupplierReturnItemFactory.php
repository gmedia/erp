<?php

namespace Database\Factories;

use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\SupplierReturn;
use App\Models\SupplierReturnItem;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierReturnItemFactory extends Factory
{
    protected $model = SupplierReturnItem::class;

    public function definition(): array
    {
        return [
            'supplier_return_id' => SupplierReturn::factory(),
            'goods_receipt_item_id' => GoodsReceiptItem::factory(),
            'product_id' => Product::factory(),
            'unit_id' => Unit::factory(),
            'quantity_returned' => $this->faker->randomFloat(2, 1, 20),
            'unit_price' => $this->faker->randomFloat(2, 1000, 100000),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
