<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Product;
use App\Models\SupplierBill;
use App\Models\SupplierBillItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierBillItemFactory extends Factory
{
    protected $model = SupplierBillItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->randomFloat(2, 1, 100);
        $unitPrice = $this->faker->randomFloat(2, 10000, 5000000);
        $discountPercent = $this->faker->randomFloat(2, 0, 10);
        $taxPercent = 11.00;
        $subtotal = $quantity * $unitPrice;
        $discount = $subtotal * ($discountPercent / 100);
        $tax = ($subtotal - $discount) * ($taxPercent / 100);
        $lineTotal = $subtotal - $discount + $tax;

        return [
            'supplier_bill_id' => SupplierBill::factory(),
            'product_id' => Product::factory(),
            'account_id' => Account::factory(),
            'description' => $this->faker->sentence(3),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_percent' => $discountPercent,
            'tax_percent' => $taxPercent,
            'line_total' => round($lineTotal, 2),
            'goods_receipt_item_id' => null,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
