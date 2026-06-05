<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\FiscalYear;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ulid' => Str::ulid()->toBase32(),
            'fiscal_year_id' => FiscalYear::factory(),
            'name' => fake()->words(3, true) . ' Budget',
            'description' => fake()->optional()->sentence(),
            'budget_type' => 'operational',
            'status' => 'draft',
            'total_amount' => 0,
            'created_by' => User::factory(),
        ];
    }

    public function approved(): static
    {
        return $this->state([
            'status' => 'approved',
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }

    public function locked(): static
    {
        return $this->state([
            'status' => 'locked',
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => 'cancelled']);
    }

    public function operational(): static
    {
        return $this->state(['budget_type' => 'operational']);
    }

    public function capital(): static
    {
        return $this->state(['budget_type' => 'capital']);
    }
}
