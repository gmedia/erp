<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\RecurringJournal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecurringJournalLine>
 */
class RecurringJournalLineFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isDebit = $this->faker->boolean();
        $amount = $this->faker->randomFloat(2, 100, 10000);

        return [
            'recurring_journal_id' => RecurringJournal::factory(),
            'account_id' => Account::factory(),
            'debit' => $isDebit ? $amount : 0,
            'credit' => $isDebit ? 0 : $amount,
            'memo' => $this->faker->optional()->sentence(),
        ];
    }

    public function debit(float $amount): static
    {
        return $this->state(['debit' => $amount, 'credit' => 0]);
    }

    public function credit(float $amount): static
    {
        return $this->state(['debit' => 0, 'credit' => $amount]);
    }
}
