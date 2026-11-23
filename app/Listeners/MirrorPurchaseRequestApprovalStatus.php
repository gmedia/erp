<?php

namespace App\Listeners;

use App\Events\ApprovalRequestApproved;
use App\Events\ApprovalRequestRejected;
use App\Models\PurchaseRequest;

class MirrorPurchaseRequestApprovalStatus
{
    public function handle(ApprovalRequestApproved|ApprovalRequestRejected $event): void
    {
        $approval = $event->approvalRequest;

        if ($approval->approvable_type !== PurchaseRequest::class) {
            return;
        }

        /** @var PurchaseRequest|null $purchaseRequest */
        $purchaseRequest = PurchaseRequest::find($approval->approvable_id);

        if (! $purchaseRequest) {
            return;
        }

        if ($event instanceof ApprovalRequestRejected) {
            $purchaseRequest->update([
                'status' => 'rejected',
                'rejection_reason' => $event->reason,
            ]);

            return;
        }

        if (! $event->final) {
            return;
        }

        $finalApproverId = $approval->steps()
            ->where('status', 'approved')
            ->orderByDesc('step_order')
            ->value('acted_by');

        $purchaseRequest->update([
            'status' => 'approved',
            'approved_at' => $approval->completed_at ?? now(),
            'approved_by' => $finalApproverId ?? $event->actorUserId,
            'rejection_reason' => null,
        ]);
    }
}
