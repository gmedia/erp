<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\FiscalYear;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class SupplierBillFactory extends Factory
{
    protected $model = SupplierBill::class;

    public function definition(): array
    {
        // Relatif terhadap now() (ikut Carbon::setTestNow) agar tanggal dokumen
        // tidak pernah melewati as_of yang dibekukan test.
        $billDate = now()->subDays($this->faker->numberBetween(1, 60));

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
            'bill_date' => $billDate->format('Y-m-d'),
            'due_date' => $this->faker->dateTimeBetween($billDate->copy()->addDays(7), $billDate->copy()->addDays(60))->format('Y-m-d'),
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
        return $this->state(function (array $attributes) {
            $billDate = Carbon::parse($attributes['bill_date']);

            return [
                'status' => 'overdue',
                'due_date' => $this->faker->dateTimeBetween($billDate, now()->subDay())->format('Y-m-d'),
                'confirmed_by' => User::factory(),
                'confirmed_at' => now(),
            ];
        });
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
