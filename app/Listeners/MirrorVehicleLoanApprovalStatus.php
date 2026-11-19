<?php

namespace App\Listeners;

use App\Events\ApprovalRequestApproved;
use App\Events\ApprovalRequestRejected;
use App\Models\VehicleLoan;
use App\Models\VehicleLoanActivityLog;
use Illuminate\Support\Facades\DB;

class MirrorVehicleLoanApprovalStatus
{
    public function handle(ApprovalRequestApproved|ApprovalRequestRejected $event): void
    {
        $approval = $event->approvalRequest;

        if ($approval->approvable_type !== VehicleLoan::class) {
            return;
        }

        /** @var VehicleLoan|null $loan */
        $loan = VehicleLoan::find($approval->approvable_id);

        if (! $loan) {
            return;
        }

        DB::transaction(function () use ($event, $loan, $approval): void {
            if ($event instanceof ApprovalRequestRejected) {
                $loan->update([
                    'status' => 'rejected',
                    'rejection_reason' => $event->reason,
                ]);

                VehicleLoanActivityLog::create([
                    'vehicle_loan_id' => $loan->id,
                    'event' => 'approval_rejected',
                    'actor_user_id' => $event->actorUserId,
                    'metadata' => ['reason' => $event->reason],
                ]);

                return;
            }

            if (! $event->final) {
                $loan->update(['status' => 'in_progress']);

                VehicleLoanActivityLog::create([
                    'vehicle_loan_id' => $loan->id,
                    'event' => 'approval_step_advanced',
                    'actor_user_id' => $event->actorUserId,
                    'metadata' => ['current_step_order' => $approval->current_step_order],
                ]);

                return;
            }

            /** @var int|null $finalApproverId */
            $finalApproverId = $approval->steps()
                ->where('status', 'approved')
                ->orderByDesc('step_order')
                ->value('acted_by');

            $loan->update([
                'status' => 'approved',
                'approved_at' => $approval->completed_at ?? now(),
                'approved_by' => $finalApproverId ?? $event->actorUserId,
                'rejection_reason' => null,
            ]);

            VehicleLoanActivityLog::create([
                'vehicle_loan_id' => $loan->id,
                'event' => 'approval_completed',
                'actor_user_id' => $event->actorUserId,
            ]);
        });
    }
}
