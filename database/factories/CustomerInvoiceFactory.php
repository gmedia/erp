<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\FiscalYear;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerInvoiceFactory extends Factory
{
    protected $model = CustomerInvoice::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement([
            'draft',
            'sent',
            'partially_paid',
            'paid',
            'overdue',
            'cancelled',
            'void',
        ]);

        $grandTotal = $this->faker->randomFloat(2, 100000, 10000000);
        $amountReceived = in_array($status, ['partially_paid', 'paid'], true)
            ? $this->faker->randomFloat(2, 0, $grandTotal)
            : 0;

        return [
            'invoice_number' => null,
            'customer_id' => Customer::factory(),
            'branch_id' => Branch::factory(),
            'fiscal_year_id' => FiscalYear::factory(),
            'invoice_date' => $this->faker->date(),
            'due_date' => $this->faker->dateTimeBetween('now', '+60 days')->format('Y-m-d'),
            'payment_terms' => $this->faker->optional()->randomElement(['Net 7', 'Net 14', 'Net 30', 'Net 60', 'COD']),
            'currency' => 'IDR',
            'subtotal' => $this->faker->randomFloat(2, 100000, 10000000),
            'tax_amount' => $this->faker->randomFloat(2, 0, 1000000),
            'discount_amount' => $this->faker->randomFloat(2, 0, 500000),
            'grand_total' => $grandTotal,
            'amount_received' => $amountReceived,
            'credit_note_amount' => 0,
            'amount_due' => $grandTotal - $amountReceived,
            'status' => $status,
            'notes' => $this->faker->optional()->sentence(),
            'journal_entry_id' => null,
            'created_by' => User::factory(),
            'sent_by' => in_array($status, ['sent', 'partially_paid', 'paid', 'overdue', 'void'], true) ? User::factory() : null,
            'sent_at' => in_array($status, ['sent', 'partially_paid', 'paid', 'overdue', 'void'], true) ? now() : null,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'sent_by' => User::factory(),
            'sent_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            $grandTotal = $attributes['grand_total'] ?? 1000000;

            return [
                'status' => 'paid',
                'amount_received' => $grandTotal,
                'amount_due' => 0,
                'sent_by' => User::factory(),
                'sent_at' => now(),
            ];
        });
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'overdue',
            'due_date' => $this->faker->dateTimeBetween('-30 days', '-1 day')->format('Y-m-d'),
            'sent_by' => User::factory(),
            'sent_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
