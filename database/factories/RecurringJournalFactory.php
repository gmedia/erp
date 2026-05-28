<?php

namespace Database\Factories;

use App\Models\FiscalYear;
use App\Models\RecurringJournal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringJournal>
 */
class RecurringJournalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->sentence(),
            'fiscal_year_id' => FiscalYear::factory(),
            'frequency' => $this->faker->randomElement(['monthly', 'quarterly', 'semi_annual', 'annual']),
            'next_run_date' => $this->faker->dateTimeBetween('now', '+3 months'),
            'last_run_date' => $this->faker->optional()->dateTimeBetween('-3 months', 'now'),
            'end_date' => $this->faker->optional()->dateTimeBetween('+6 months', '+2 years'),
            'total_amount' => $this->faker->randomFloat(2, 100, 50000),
            'auto_post' => $this->faker->boolean(30),
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function autoPost(): static
    {
        return $this->state(['auto_post' => true]);
    }
}
