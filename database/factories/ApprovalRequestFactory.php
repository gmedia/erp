<?php

namespace Database\Factories;

use App\Models\ApprovalFlow;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApprovalRequest>
 */
class ApprovalRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'approvable_type' => 'App\Models\Customer', // Dummy approvable
            'approvable_id' => 1,
            'approval_flow_id' => ApprovalFlow::factory(),
            'submitted_by' => User::factory(),
            'submitted_at' => now(),
            'status' => 'pending',
            'current_step_order' => 1,
            'completed_at' => null,
        ];
    }
}
