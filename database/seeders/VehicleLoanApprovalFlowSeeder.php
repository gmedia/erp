<?php

namespace Database\Seeders;

use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class VehicleLoanApprovalFlowSeeder extends Seeder
{
    public function run(): void
    {
        $approverEmail = Setting::get('vehicle_loan_approver_email');
        $fallbackEmail = config('app.admin');

        $approver = null;

        if (is_string($approverEmail) && $approverEmail !== '') {
            $approver = User::where('email', $approverEmail)->first();
        }

        if (! $approver && is_string($fallbackEmail) && $fallbackEmail !== '') {
            $approver = User::where('email', $fallbackEmail)->first();
        }

        if (! $approver) {
            // Intentionally empty: configure the approver via Setting or the
            // Approval Flows UI. Seeding a flow without a valid approver would
            // break TriggerApprovalAction, which requires an approver_user_id.
            return;
        }

        $flow = ApprovalFlow::updateOrCreate(
            ['code' => config('vehicle_loan.approval_flow_code')],
            [
                'name' => 'Vehicle Loan Default Approval',
                'approvable_type' => config('vehicle_loan.approvable_type'),
                'description' => 'Default approval flow for vehicle loan requests.',
                'is_active' => true,
                'conditions' => null,
                'created_by' => $approver->id,
            ],
        );

        ApprovalFlowStep::updateOrCreate(
            [
                'approval_flow_id' => $flow->id,
                'step_order' => 1,
            ],
            [
                'name' => 'Vehicle Loan Approval',
                'approver_type' => 'user',
                'approver_user_id' => $approver->id,
                'required_action' => 'approve',
                'can_reject' => true,
            ],
        );
    }
}
