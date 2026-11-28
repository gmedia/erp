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

        // Kode lama memakai titik; seragamkan ke snake_case (W6-09).
        $targetCode = config('vehicle_loan.approval_flow_code');
        $legacy = ApprovalFlow::query()->where('code', 'vehicle-loan.default')->first();

        if ($legacy) {
            if (ApprovalFlow::query()->where('code', $targetCode)->exists()) {
                $legacy->delete();
            } else {
                $legacy->update(['code' => $targetCode]);
            }
        }

        // Master dibuat sebelum transaksi pertama; created_at hanya saat insert (idempoten).
        $isNew = ! ApprovalFlow::query()->where('code', $targetCode)->exists();

        $flow = ApprovalFlow::updateOrCreate(
            ['code' => $targetCode],
            [
                'name' => 'Persetujuan Default Peminjaman Kendaraan',
                'approvable_type' => config('vehicle_loan.approvable_type'),
                'description' => 'Default approval flow for vehicle loan requests.',
                'is_active' => true,
                'conditions' => null,
                'created_by' => $approver->id,
            ] + ($isNew ? ['created_at' => now()->subMonths(14)] : []),
        );

        $steps = [
            1 => 'Verifikasi Atasan Peminjaman Kendaraan',
            2 => 'Persetujuan Final Peminjaman Kendaraan',
        ];

        foreach ($steps as $stepOrder => $stepName) {
            ApprovalFlowStep::updateOrCreate(
                [
                    'approval_flow_id' => $flow->id,
                    'step_order' => $stepOrder,
                ],
                [
                    'name' => $stepName,
                    'approver_type' => 'user',
                    'approver_user_id' => $approver->id,
                    'required_action' => 'approve',
                    'can_reject' => true,
                ],
            );
        }
    }
}
