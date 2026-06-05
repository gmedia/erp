<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Budget;
use App\Models\BudgetLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetLine>
 */
class BudgetLineFactory extends Factory
{
    protected $model = BudgetLine::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $periodStart = $this->faker->dateTimeBetween('first day of this month', 'first day of this month');

        return [
            'budget_id' => Budget::factory(),
            'account_id' => Account::factory(),
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => now()->endOfMonth()->format('Y-m-d'),
            'allocated_amount' => $this->faker->randomFloat(2, 1000, 100000),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
