<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreditNoteItemFactory extends Factory
{
    protected $model = CreditNoteItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->randomFloat(2, 1, 50);
        $unitPrice = $this->faker->randomFloat(2, 10000, 500000);
        $taxPercent = $this->faker->randomFloat(2, 0, 11);

        $subtotal = $quantity * $unitPrice;
        $tax = $subtotal * ($taxPercent / 100);
        $lineTotal = $subtotal + $tax;

        return [
            'credit_note_id' => CreditNote::factory(),
            'product_id' => Product::factory(),
            'account_id' => Account::factory(),
            'description' => $this->faker->sentence(3),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_percent' => $taxPercent,
            'line_total' => $lineTotal,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
