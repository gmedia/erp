<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\FiscalYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AccountBalance>
 */
class AccountBalanceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $debitTotal = $this->faker->randomFloat(2, 0, 50000);
        $creditTotal = $this->faker->randomFloat(2, 0, 50000);
        $openingBalance = $this->faker->randomFloat(2, -10000, 50000);
        $movement = $debitTotal - $creditTotal;
        $closingBalance = $openingBalance + $movement;

        return [
            'account_id' => Account::factory(),
            'fiscal_year_id' => FiscalYear::factory(),
            'period_month' => $this->faker->numberBetween(1, 12),
            'period_year' => $this->faker->numberBetween(2024, 2026),
            'opening_balance' => $openingBalance,
            'debit_total' => $debitTotal,
            'credit_total' => $creditTotal,
            'closing_balance' => $closingBalance,
            'movement' => $movement,
            'last_recalculated_at' => now(),
        ];
    }
}
