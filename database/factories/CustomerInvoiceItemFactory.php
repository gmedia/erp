<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\CustomerInvoice;
use App\Models\CustomerInvoiceItem;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerInvoiceItemFactory extends Factory
{
    protected $model = CustomerInvoiceItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->randomFloat(2, 1, 100);
        $unitPrice = $this->faker->randomFloat(2, 10000, 500000);
        $discountPercent = $this->faker->randomFloat(2, 0, 10);
        $taxPercent = $this->faker->randomFloat(2, 0, 11);

        $subtotal = $quantity * $unitPrice;
        $discount = $subtotal * ($discountPercent / 100);
        $taxableAmount = $subtotal - $discount;
        $tax = $taxableAmount * ($taxPercent / 100);
        $lineTotal = $taxableAmount + $tax;

        return [
            'customer_invoice_id' => CustomerInvoice::factory(),
            'product_id' => Product::factory(),
            'account_id' => Account::factory(),
            'description' => $this->faker->sentence(3),
            'quantity' => $quantity,
            'unit_id' => Unit::factory(),
            'unit_price' => $unitPrice,
            'discount_percent' => $discountPercent,
            'tax_percent' => $taxPercent,
            'line_total' => $lineTotal,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
