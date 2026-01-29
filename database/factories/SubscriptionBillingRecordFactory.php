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
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $periodStart = fake()->dateTimeBetween('-3 months', 'now');
        $periodEnd = (clone $periodStart)->modify('+1 month');
        $billingDate = $periodStart;
        $dueDate = (clone $billingDate)->modify('+7 days');

        $status = fake()->randomElement(['pending', 'paid', 'overdue', 'cancelled']);
        
        $subtotal = fake()->numberBetween(10, 500) * 1000;
        $taxAmount = $subtotal * 0.11;
        $discountAmount = fake()->optional(0.2)->numberBetween(0, $subtotal * 0.2);
        $totalAmount = $subtotal + $taxAmount - ($discountAmount ?? 0);
        
        $amountPaid = 0;
        $paidDate = null;
        if ($status === 'paid') {
            $amountPaid = $totalAmount;
            $paidDate = fake()->dateTimeBetween($billingDate, 'now');
        } elseif ($status === 'partially_paid') {
            $amountPaid = $totalAmount * fake()->randomFloat(2, 0.3, 0.9);
        }

        return [
            'customer_subscription_id' => CustomerSubscription::factory(),
            'invoice_number' => 'INV-SUB-' . date('Ymd') . '-' . fake()->unique()->numberBetween(1000, 9999),
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'billing_date' => $billingDate,
            'due_date' => $dueDate,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount ?? 0,
            'total_amount' => $totalAmount,
            'amount_paid' => $amountPaid,
            'status' => $status,
            'paid_date' => $paidDate,
            'payment_method' => $status === 'paid' ? fake()->randomElement(['credit_card', 'bank_transfer', 'cash']) : null,
            'payment_reference' => $status === 'paid' ? fake()->uuid() : null,
            'retry_count' => $status === 'overdue' ? fake()->numberBetween(0, 3) : 0,
            'next_retry_date' => $status === 'overdue' ? fake()->dateTimeBetween('now', '+7 days') : null,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the invoice is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'amount_paid' => 0,
            'paid_date' => null,
            'payment_method' => null,
            'payment_reference' => null,
        ]);
    }

    /**
     * Indicate that the invoice is paid.
     */
    public function paid(): static
    {
        $paidDate = fake()->dateTimeBetween('-30 days', 'now');
        
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'amount_paid' => $attributes['total_amount'],
            'paid_date' => $paidDate,
            'payment_method' => fake()->randomElement(['credit_card', 'bank_transfer', 'cash']),
            'payment_reference' => fake()->uuid(),
        ]);
    }

    /**
     * Indicate that the invoice is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'overdue',
            'due_date' => now()->subDays(fake()->numberBetween(1, 30)),
            'retry_count' => fake()->numberBetween(1, 3),
            'next_retry_date' => fake()->dateTimeBetween('now', '+7 days'),
        ]);
    }
}
