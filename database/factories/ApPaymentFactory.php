<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\ApPayment;
use App\Models\Branch;
use App\Models\FiscalYear;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApPaymentFactory extends Factory
{
    protected $model = ApPayment::class;

    public function definition(): array
    {
        $totalAmount = $this->faker->randomFloat(2, 500000, 50000000);

        return [
            'payment_number' => null,
            'supplier_id' => Supplier::factory(),
            'branch_id' => Branch::factory(),
            'fiscal_year_id' => FiscalYear::factory(),
            'payment_date' => $this->faker->date(),
            'payment_method' => $this->faker->randomElement(['bank_transfer', 'cash', 'check', 'giro', 'other']),
            'bank_account_id' => Account::factory(),
            'currency' => 'IDR',
            'total_amount' => $totalAmount,
            'total_allocated' => 0,
            'total_unallocated' => $totalAmount,
            'reference' => $this->faker->optional()->numerify('TRF-########'),
            'status' => 'draft',
            'notes' => $this->faker->optional()->sentence(),
            'journal_entry_id' => null,
            'approved_by' => null,
            'approved_at' => null,
            'created_by' => User::factory(),
            'confirmed_by' => null,
            'confirmed_at' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
            'total_allocated' => $attributes['total_amount'],
            'total_unallocated' => 0,
            'confirmed_by' => User::factory(),
            'confirmed_at' => now(),
        ]);
    }

    public function pendingApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending_approval',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
