<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ApprovalFlowSampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = \App\Models\User::where('email', config('app.admin'))->first();
        $hrManager = \App\Models\User::where('email', 'manager.hr@dokfin.id')->first();
        $financeDirector = \App\Models\User::where('email', 'director.finance@dokfin.id')->first();
        $itStaff = \App\Models\User::where('email', 'staff.it@dokfin.id')->first();

        $hrDept = \App\Models\Department::where('name', 'HR')->first();
        $financeDept = \App\Models\Department::where('name', 'Finance')->first();

        // Flow 1: High Value Asset Registration
        $flow1 = \App\Models\ApprovalFlow::firstOrCreate(
            ['code' => 'asset_registration_high_value'],
            [
                'name' => 'High Value Asset Registration',
                'approvable_type' => 'App\\Models\\Asset',
                'description' => 'Approval for registering assets with cost > 100M',
                'is_active' => true,
                'conditions' => ['field_checks' => [['field' => 'purchase_cost', 'operator' => '>', 'value' => 100000000]]],
                'created_by' => $admin->id,
            ]
        );

        \App\Models\ApprovalFlowStep::firstOrCreate(
            [
                'approval_flow_id' => $flow1->id,
                'step_order' => 1,
            ],
            [
                'name' => 'HR Manager Review',
                'approver_type' => 'user',
                'approver_user_id' => $hrManager->id,
            ]
        );

        \App\Models\ApprovalFlowStep::firstOrCreate(
            [
                'approval_flow_id' => $flow1->id,
                'step_order' => 2,
            ],
            [
                'name' => 'Finance Director Approval',
                'approver_type' => 'user',
                'approver_user_id' => $financeDirector->id,
            ]
        );

        // Seed a dummy Approval Audit Log for UI testing
        $dummyRequest = \App\Models\ApprovalRequest::firstOrCreate(
            [
                'approval_flow_id' => $flow1->id,
                'approvable_type' => 'App\\Models\\Asset',
                'approvable_id' => 1,
            ],
            [
                'status' => 'pending',
                'current_step_order' => 1,
                'submitted_by' => $admin->id,
            ]
        );

        \App\Models\ApprovalAuditLog::firstOrCreate(
            [
                'approval_request_id' => $dummyRequest->id,
                'event' => 'submitted',
            ],
            [
                'approvable_type' => 'App\\Models\\Asset',
                'approvable_id' => 1,
                'actor_user_id' => $admin->id,
                'step_order' => 1,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0',
                'metadata' => ['note' => 'Test submission'],
            ]
        );

    }
}
