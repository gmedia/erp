<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerSubscription;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomerSubscription>
 */
class CustomerSubscriptionFactory extends Factory
{
    protected $model = CustomerSubscription::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-6 months', '-1 month');
        $status = fake()->randomElement(['trial', 'active', 'past_due', 'cancelled', 'expired']);

        return [
            'customer_id' => Customer::factory(),
            'subscription_plan_id' => SubscriptionPlan::factory(),
            'start_date' => $startDate,
            'end_date' => $status === 'expired' ? fake()->dateTimeBetween($startDate, 'now') : null,
            'next_billing_date' => fake()->dateTimeBetween('now', '+1 month'),
            'status' => $status,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function trial(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'trial',
            'start_date' => now()->subDays(fake()->numberBetween(1, 14)),
            'next_billing_date' => now()->addDays(fake()->numberBetween(1, 14)),
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'next_billing_date' => now()->addDays(fake()->numberBetween(1, 30)),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'end_date' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }
}
