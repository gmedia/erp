<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\FiscalYear;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierBillFactory extends Factory
{
    protected $model = SupplierBill::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 500000, 50000000);
        $taxAmount = round($subtotal * 0.11, 2);
        $discountAmount = $this->faker->randomFloat(2, 0, $subtotal * 0.05);
        $grandTotal = $subtotal + $taxAmount - $discountAmount;

        return [
            'bill_number' => null,
            'supplier_id' => Supplier::factory(),
            'branch_id' => Branch::factory(),
            'fiscal_year_id' => FiscalYear::factory(),
            'purchase_order_id' => null,
            'goods_receipt_id' => null,
            'supplier_invoice_number' => $this->faker->optional()->numerify('INV-####'),
            'supplier_invoice_date' => $this->faker->optional()->date(),
            'bill_date' => $this->faker->date(),
            'due_date' => $this->faker->dateTimeBetween('+7 days', '+60 days')->format('Y-m-d'),
            'payment_terms' => $this->faker->optional()->randomElement(['Net 7', 'Net 14', 'Net 30', 'Net 60']),
            'currency' => 'IDR',
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'grand_total' => $grandTotal,
            'amount_paid' => 0,
            'amount_due' => $grandTotal,
            'status' => 'draft',
            'notes' => $this->faker->optional()->sentence(),
            'journal_entry_id' => null,
            'created_by' => User::factory(),
            'confirmed_by' => null,
            'confirmed_at' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
            'confirmed_by' => User::factory(),
            'confirmed_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'amount_paid' => $attributes['grand_total'],
            'amount_due' => 0,
            'confirmed_by' => User::factory(),
            'confirmed_at' => now(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'overdue',
            'due_date' => $this->faker->dateTimeBetween('-30 days', '-1 day')->format('Y-m-d'),
            'confirmed_by' => User::factory(),
            'confirmed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
