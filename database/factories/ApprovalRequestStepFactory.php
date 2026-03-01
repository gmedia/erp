<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApprovalRequestStep>
 */
class ApprovalRequestStepFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'approval_request_id' => \App\Models\ApprovalRequest::factory(),
            'approval_flow_step_id' => \App\Models\ApprovalFlowStep::factory(),
            'step_order' => 1,
            'status' => 'pending',
            'comments' => null,
        ];
    }
}
