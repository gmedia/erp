<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\FiscalYear;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BankReconciliation>
 */
class BankReconciliationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $periodStart = $this->faker->dateTimeBetween('-3 months', '-1 month');
        $periodEnd = (clone $periodStart)->modify('+1 month -1 day');
        $statementBalance = $this->faker->randomFloat(2, 10000, 500000);

        return [
            'account_id' => Account::factory(),
            'fiscal_year_id' => FiscalYear::factory(),
            'reconciliation_date' => $this->faker->dateTimeBetween($periodEnd, 'now'),
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'statement_balance' => $statementBalance,
            'book_balance' => $statementBalance + $this->faker->randomFloat(2, -5000, 5000),
            'reconciled_balance' => $statementBalance,
            'difference' => 0,
            'status' => 'in_progress',
            'created_by' => User::factory(),
        ];
    }

    public function completed(): static
    {
        return $this->state([
            'status' => 'completed',
            'difference' => 0,
            'completed_by' => User::factory(),
            'completed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => 'cancelled']);
    }
}
