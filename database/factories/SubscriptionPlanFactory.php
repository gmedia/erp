<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubscriptionPlan>
 */
class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $interval = fake()->randomElement(['monthly', 'quarterly', 'annual']);
        $price = fake()->numberBetween(10, 500) * 1000; // 10k - 500k
        
        $planNames = [
            'monthly' => 'Monthly Plan',
            'quarterly' => 'Quarterly Plan',
            'annual' => 'Annual Plan',
        ];

        return [
            'product_id' => Product::factory()->subscription(),
            'name' => $planNames[$interval],
            'code' => 'PLAN-' . strtoupper(substr($interval, 0, 3)) . '-' . fake()->unique()->numberBetween(100, 999),
            'description' => fake()->optional()->sentence(),
            'billing_interval' => $interval,
            'billing_interval_count' => 1,
            'price' => $price,
            'setup_fee' => fake()->optional(0.3)->numberBetween(50, 200) * 1000,
            'trial_period_days' => fake()->optional(0.5)->randomElement([7, 14, 30]),
            'minimum_commitment_cycles' => fake()->optional(0.4)->randomElement([3, 6, 12]),
            'auto_renew' => fake()->boolean(80),
            'status' => fake()->randomElement(['active', 'inactive', 'archived']),
        ];
    }

    /**
     * Indicate that the plan is monthly.
     */
    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Monthly Plan',
            'billing_interval' => 'monthly',
            'billing_interval_count' => 1,
        ]);
    }

    /**
     * Indicate that the plan is quarterly.
     */
    public function quarterly(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Quarterly Plan',
            'billing_interval' => 'quarterly',
            'billing_interval_count' => 1,
        ]);
    }

    /**
     * Indicate that the plan is annual.
     */
    public function annual(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Annual Plan',
            'billing_interval' => 'annual',
            'billing_interval_count' => 1,
        ]);
    }

    /**
     * Indicate that the plan is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }
}
