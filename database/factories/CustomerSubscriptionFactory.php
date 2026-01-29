<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerSubscription;
use App\Models\Product;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomerSubscription>
 */
class CustomerSubscriptionFactory extends Factory
{
    protected $model = CustomerSubscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(['trial', 'active', 'past_due', 'cancelled']);
        $startDate = fake()->dateTimeBetween('-6 months', '-1 month');
        $currentPeriodStart = fake()->dateTimeBetween($startDate, 'now');
        $currentPeriodEnd = (clone $currentPeriodStart)->modify('+1 month');

        $trialStartDate = null;
        $trialEndDate = null;
        if ($status === 'trial') {
            $trialStartDate = now()->subDays(fake()->numberBetween(1, 14));
            $trialEndDate = (clone $trialStartDate)->modify('+14 days');
        }

        $cancellationDate = null;
        $cancellationEffectiveDate = null;
        if ($status === 'cancelled') {
            $cancellationDate = fake()->dateTimeBetween($startDate, 'now');
            $cancellationEffectiveDate = (clone $cancellationDate)->modify('+30 days');
        }

        return [
            'subscription_number' => 'SUB-' . date('Ymd') . '-' . fake()->unique()->numberBetween(1000, 9999),
            'customer_id' => Customer::factory(),
            'subscription_plan_id' => SubscriptionPlan::factory(),
            'product_id' => Product::factory()->subscription(),
            'status' => $status,
            'trial_start_date' => $trialStartDate,
            'trial_end_date' => $trialEndDate,
            'start_date' => $startDate,
            'current_period_start' => $currentPeriodStart,
            'current_period_end' => $currentPeriodEnd,
            'cancellation_date' => $cancellationDate,
            'cancellation_effective_date' => $cancellationEffectiveDate,
            'billing_cycles_completed' => $status === 'active' ? fake()->numberBetween(1, 12) : 0,
            'auto_renew' => fake()->boolean(80),
            'recurring_amount' => fake()->numberBetween(10, 500) * 1000,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the subscription is in trial.
     */
    public function trial(): static
    {
        $trialStartDate = now()->subDays(fake()->numberBetween(1, 14));
        
        return $this->state(fn (array $attributes) => [
            'status' => 'trial',
            'trial_start_date' => $trialStartDate,
            'trial_end_date' => (clone $trialStartDate)->modify('+14 days'),
            'billing_cycles_completed' => 0,
        ]);
    }

    /**
     * Indicate that the subscription is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'trial_start_date' => null,
            'trial_end_date' => null,
            'billing_cycles_completed' => fake()->numberBetween(1, 12),
        ]);
    }

    /**
     * Indicate that the subscription is cancelled.
     */
    public function cancelled(): static
    {
        $cancellationDate = now()->subDays(fake()->numberBetween(1, 30));
        
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancellation_date' => $cancellationDate,
            'cancellation_effective_date' => (clone $cancellationDate)->modify('+30 days'),
        ]);
    }
}
