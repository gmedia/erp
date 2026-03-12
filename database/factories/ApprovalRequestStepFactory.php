<?php

namespace Database\Factories;

use App\Models\ApprovalFlowStep;
use App\Models\ApprovalRequest;
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
            'approval_request_id' => ApprovalRequest::factory(),
            'approval_flow_step_id' => ApprovalFlowStep::factory(),
            'step_order' => 1,
            'status' => 'pending',
            'comments' => null,
        ];
    }
}
