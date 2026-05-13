<?php

namespace Database\Factories;

use App\Models\BankReconciliation;
use App\Models\JournalEntryLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BankReconciliationItem>
 */
class BankReconciliationItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isDebit = $this->faker->boolean();
        $amount = $this->faker->randomFloat(2, 100, 10000);

        return [
            'bank_reconciliation_id' => BankReconciliation::factory(),
            'journal_entry_line_id' => null,
            'transaction_date' => $this->faker->dateTimeBetween('-2 months', 'now'),
            'description' => $this->faker->sentence(),
            'debit' => $isDebit ? $amount : 0,
            'credit' => $isDebit ? 0 : $amount,
            'type' => $this->faker->randomElement(['matched', 'outstanding_check', 'deposit_in_transit', 'bank_charge', 'bank_interest', 'error', 'other']),
            'is_reconciled' => false,
            'reference' => $this->faker->optional()->numerify('REF-####'),
        ];
    }

    public function matched(): static
    {
        return $this->state([
            'type' => 'matched',
            'is_reconciled' => true,
            'journal_entry_line_id' => JournalEntryLine::factory(),
        ]);
    }

    public function outstandingCheck(): static
    {
        return $this->state(['type' => 'outstanding_check']);
    }

    public function bankCharge(): static
    {
        return $this->state(['type' => 'bank_charge']);
    }
}
