<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderItemFactory extends Factory
{
    protected $model = PurchaseOrderItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->randomFloat(2, 1, 100);
        $unitPrice = $this->faker->randomFloat(2, 1000, 100000);
        $discountPercent = $this->faker->randomFloat(2, 0, 20);
        $taxPercent = $this->faker->randomFloat(2, 0, 11);

        $lineBeforeTax = $quantity * $unitPrice * (1 - ($discountPercent / 100));
        $lineTotal = $lineBeforeTax * (1 + ($taxPercent / 100));

        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'purchase_request_item_id' => null,
            'product_id' => Product::factory(),
            'unit_id' => Unit::factory(),
            'quantity' => $quantity,
            'quantity_received' => 0,
            'unit_price' => $unitPrice,
            'discount_percent' => $discountPercent,
            'tax_percent' => $taxPercent,
            'line_total' => $lineTotal,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
