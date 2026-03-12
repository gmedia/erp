<?php

namespace Database\Factories;

use App\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApprovalAuditLog>
 */
class ApprovalAuditLogFactory extends Factory
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
            'approvable_type' => 'App\Models\Customer',
            'approvable_id' => 1,
            'event' => 'step_approved',
            'actor_user_id' => User::factory(),
            'step_order' => 1,
            'metadata' => json_encode(['comments' => 'LGTM']),
        ];
    }
}
