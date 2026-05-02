<?php

namespace Database\Factories;

use App\Models\CustomerSubscription;
use App\Models\SubscriptionBillingRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubscriptionBillingRecord>
 */
class SubscriptionBillingRecordFactory extends Factory
{
    protected $model = SubscriptionBillingRecord::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $periodStart = fake()->dateTimeBetween('-3 months', 'now');
        $periodEnd = (clone $periodStart)->modify('+1 month');
        $status = fake()->randomElement(['pending', 'paid', 'overdue', 'cancelled']);

        $amount = fake()->numberBetween(10, 500) * 1000;
        $taxAmount = round($amount * 0.11, 2);
        $discountAmount = fake()->boolean(20) ? fake()->numberBetween(0, (int) ($amount * 0.2)) : 0;
        $total = $amount + $taxAmount - $discountAmount;

        return [
            'customer_subscription_id' => CustomerSubscription::factory(),
            'billing_period_start' => $periodStart,
            'billing_period_end' => $periodEnd,
            'amount' => $amount,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total' => $total,
            'status' => $status,
            'paid_at' => $status === 'paid' ? fake()->dateTimeBetween($periodStart, 'now') : null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'paid_at' => null,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'paid_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'overdue',
            'paid_at' => null,
        ]);
    }
}
