<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApprovalFlow>
 */
class ApprovalFlowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true) . ' Approval',
            'code' => $this->faker->unique()->slug(),
            'approvable_type' => 'App\\Models\\PurchaseRequest',
            'description' => $this->faker->sentence(),
            'is_active' => true,
            'conditions' => null,
            'created_by' => User::factory(),
        ];
    }
}
