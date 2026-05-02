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
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory()->subscription(),
            'name' => fake()->randomElement(['Monthly Plan', 'Quarterly Plan', 'Annual Plan']),
            'billing_interval' => fake()->randomElement(['monthly', 'quarterly', 'annual']),
            'price' => fake()->numberBetween(10, 500) * 1000,
            'setup_fee' => fake()->boolean(30) ? fake()->numberBetween(50, 200) * 1000 : 0,
            'trial_period_days' => fake()->randomElement([0, 7, 14, 30]),
            'is_active' => fake()->boolean(80),
        ];
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Monthly Plan',
            'billing_interval' => 'monthly',
        ]);
    }

    public function quarterly(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Quarterly Plan',
            'billing_interval' => 'quarterly',
        ]);
    }

    public function annual(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Annual Plan',
            'billing_interval' => 'annual',
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
}
