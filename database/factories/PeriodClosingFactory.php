<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\FiscalYear;
use App\Models\PeriodClosing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PeriodClosing>
 */
class PeriodClosingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fiscal_year_id' => FiscalYear::factory(),
            'period_month' => $this->faker->numberBetween(1, 12),
            'period_year' => $this->faker->numberBetween(2024, 2026),
            'closing_type' => 'monthly',
            'status' => 'draft',
            'retained_earnings_account_id' => Account::factory(),
            'net_income' => $this->faker->randomFloat(2, -50000, 200000),
            'created_by' => User::factory(),
        ];
    }

    public function closed(): static
    {
        return $this->state([
            'status' => 'closed',
            'closed_by' => User::factory(),
            'closed_at' => now(),
        ]);
    }

    public function annual(): static
    {
        return $this->state([
            'closing_type' => 'annual',
            'period_month' => null,
        ]);
    }
}
