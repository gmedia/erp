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
        $admin = \App\Models\User::where('email', config('app.admin'))->first();
        $hrManager = \App\Models\User::where('email', 'manager.hr@dokfin.id')->first();
        $financeDirector = \App\Models\User::where('email', 'director.finance@dokfin.id')->first();
        $itStaff = \App\Models\User::where('email', 'staff.it@dokfin.id')->first();

        $hrDept = \App\Models\Department::where('name', 'HR')->first();
        $financeDept = \App\Models\Department::where('name', 'Finance')->first();

        // Flow 1: High Value Asset Registration
        $flow1 = \App\Models\ApprovalFlow::create([
            'name' => 'High Value Asset Registration',
            'code' => 'asset_registration_high_value',
            'approvable_type' => 'App\\Models\\Asset',
            'description' => 'Approval for registering assets with cost > 100M',
            'is_active' => true,
            'conditions' => ['field_checks' => [['field' => 'purchase_cost', 'operator' => '>', 'value' => 100000000]]],
            'created_by' => $admin->id,
        ]);

        \App\Models\ApprovalFlowStep::create([
            'approval_flow_id' => $flow1->id,
            'step_order' => 1,
            'name' => 'HR Manager Review',
            'approver_type' => 'user',
            'approver_user_id' => $hrManager->id,
        ]);

        \App\Models\ApprovalFlowStep::create([
            'approval_flow_id' => $flow1->id,
            'step_order' => 2,
            'name' => 'Finance Director Approval',
            'approver_type' => 'user',
            'approver_user_id' => $financeDirector->id,
        ]);

    }
}
