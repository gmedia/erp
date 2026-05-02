<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\FiscalYear;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreditNoteFactory extends Factory
{
    protected $model = CreditNote::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement([
            'draft',
            'confirmed',
            'applied',
            'cancelled',
            'void',
        ]);

        return [
            'credit_note_number' => null,
            'customer_id' => Customer::factory(),
            'customer_invoice_id' => $this->faker->optional(0.7)->passthrough(CustomerInvoice::factory()),
            'branch_id' => Branch::factory(),
            'fiscal_year_id' => FiscalYear::factory(),
            'credit_note_date' => $this->faker->date(),
            'reason' => $this->faker->randomElement(['return', 'discount', 'correction', 'bad_debt', 'other']),
            'subtotal' => $this->faker->randomFloat(2, 50000, 2000000),
            'tax_amount' => $this->faker->randomFloat(2, 0, 200000),
            'grand_total' => $this->faker->randomFloat(2, 50000, 2000000),
            'status' => $status,
            'notes' => $this->faker->optional()->sentence(),
            'journal_entry_id' => null,
            'created_by' => User::factory(),
            'confirmed_by' => in_array($status, ['confirmed', 'applied', 'void'], true) ? User::factory() : null,
            'confirmed_at' => in_array($status, ['confirmed', 'applied', 'void'], true) ? now() : null,
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

    public function applied(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'applied',
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
