<?php

namespace Database\Factories;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\PurchaseOrderItem;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class GoodsReceiptItemFactory extends Factory
{
    protected $model = GoodsReceiptItem::class;

    public function definition(): array
    {
        $quantityReceived = $this->faker->randomFloat(2, 1, 100);
        $quantityRejected = $this->faker->randomFloat(2, 0, 10);
        $quantityAccepted = max(0, $quantityReceived - $quantityRejected);

        return [
            'goods_receipt_id' => GoodsReceipt::factory(),
            'purchase_order_item_id' => PurchaseOrderItem::factory(),
            'product_id' => Product::factory(),
            'unit_id' => Unit::factory(),
            'quantity_received' => $quantityReceived,
            'quantity_accepted' => $quantityAccepted,
            'quantity_rejected' => $quantityRejected,
            'unit_price' => $this->faker->randomFloat(2, 1000, 100000),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
