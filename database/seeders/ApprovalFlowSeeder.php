<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ApprovalFlowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = \App\Models\User::first() ?? \App\Models\User::factory()->create();
        $dept = \App\Models\Department::first() ?? \App\Models\Department::factory()->create();

        // Flow 1
        $flow1 = \App\Models\ApprovalFlow::create([
            'name' => 'High Value Purchase Request',
            'code' => 'pr_high_value',
            'approvable_type' => 'App\\Models\\PurchaseRequest',
            'description' => 'Approval flow for PR > 10M',
            'is_active' => true,
            'conditions' => ['field_checks' => [['field' => 'estimated_amount', 'operator' => '>', 'value' => 10000000]]],
            'created_by' => $user->id,
        ]);

        \App\Models\ApprovalFlowStep::create([
            'approval_flow_id' => $flow1->id,
            'step_order' => 1,
            'name' => 'Department Head Approval',
            'approver_type' => 'department_head',
            'approver_department_id' => $dept->id,
        ]);

        \App\Models\ApprovalFlowStep::create([
            'approval_flow_id' => $flow1->id,
            'step_order' => 2,
            'name' => 'Finance Director Approval',
            'approver_type' => 'role',
            'approver_role_id' => 1,
        ]);

        // Flow 2
        $flow2 = \App\Models\ApprovalFlow::create([
            'name' => 'Asset Disposal Standard',
            'code' => 'asset_disposal_standard',
            'approvable_type' => 'App\\Models\\AssetMovement',
            'description' => 'Standard flow for asset disposal',
            'is_active' => true,
            'conditions' => ['field_checks' => [['field' => 'movement_type', 'operator' => '=', 'value' => 'dispose']]],
            'created_by' => $user->id,
        ]);

        \App\Models\ApprovalFlowStep::create([
            'approval_flow_id' => $flow2->id,
            'step_order' => 1,
            'name' => 'IT Manager Approval',
            'approver_type' => 'user',
            'approver_user_id' => $user->id,
            'auto_approve_after_hours' => 48,
        ]);
    }
}
