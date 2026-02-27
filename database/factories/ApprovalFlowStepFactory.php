<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApprovalFlowStep>
 */
class ApprovalFlowStepFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'approval_flow_id' => \App\Models\ApprovalFlow::factory(),
            'step_order' => $this->faker->numberBetween(1, 10),
            'name' => $this->faker->jobTitle() . ' Approval',
            'approver_type' => 'user',
            'approver_user_id' => \App\Models\User::factory(),
            'approver_role_id' => null,
            'approver_department_id' => null,
            'required_action' => 'approve',
            'auto_approve_after_hours' => null,
            'escalate_after_hours' => null,
            'escalation_user_id' => null,
            'can_reject' => true,
        ];
    }
}
