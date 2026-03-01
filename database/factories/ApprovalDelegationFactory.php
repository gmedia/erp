<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApprovalDelegation>
 */
class ApprovalDelegationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'delegator_user_id' => User::factory(),
            'delegate_user_id' => User::factory(),
            'approvable_type' => $this->faker->randomElement([null, 'App\\Models\\PurchaseRequest', 'App\\Models\\PurchaseOrder', 'App\\Models\\JournalEntry']),
            'start_date' => $this->faker->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'end_date' => $this->faker->dateTimeBetween('+1 month', '+2 months')->format('Y-m-d'),
            'reason' => $this->faker->sentence(),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
        ];
    }
}
