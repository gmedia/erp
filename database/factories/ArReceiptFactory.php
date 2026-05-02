<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\ArReceipt;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\FiscalYear;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArReceiptFactory extends Factory
{
    protected $model = ArReceipt::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement([
            'draft',
            'confirmed',
            'reconciled',
            'cancelled',
            'void',
        ]);

        $totalAmount = $this->faker->randomFloat(2, 100000, 10000000);
        $totalAllocated = in_array($status, ['confirmed', 'reconciled'], true)
            ? $this->faker->randomFloat(2, 0, $totalAmount)
            : 0;

        return [
            'receipt_number' => null,
            'customer_id' => Customer::factory(),
            'branch_id' => Branch::factory(),
            'fiscal_year_id' => FiscalYear::factory(),
            'receipt_date' => $this->faker->date(),
            'payment_method' => $this->faker->randomElement(['bank_transfer', 'cash', 'check', 'giro', 'credit_card', 'other']),
            'bank_account_id' => Account::factory(),
            'currency' => 'IDR',
            'total_amount' => $totalAmount,
            'total_allocated' => $totalAllocated,
            'total_unallocated' => $totalAmount - $totalAllocated,
            'reference' => $this->faker->optional()->numerify('TRF-########'),
            'status' => $status,
            'notes' => $this->faker->optional()->sentence(),
            'journal_entry_id' => null,
            'created_by' => User::factory(),
            'confirmed_by' => in_array($status, ['confirmed', 'reconciled', 'void'], true) ? User::factory() : null,
            'confirmed_at' => in_array($status, ['confirmed', 'reconciled', 'void'], true) ? now() : null,
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

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
