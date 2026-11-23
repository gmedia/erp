<?php

namespace App\Listeners;

use App\Actions\EntityStates\SyncPipelineStateToStatusAction;
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
            $purchaseRequest->update(['rejection_reason' => $event->reason]);
            $this->mirror($purchaseRequest, 'rejected');

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
            'approved_at' => $approval->completed_at ?? now(),
            'approved_by' => $finalApproverId ?? $event->actorUserId,
            'rejection_reason' => null,
        ]);

        $this->mirror($purchaseRequest, 'approved');
    }

    private function mirror(PurchaseRequest $purchaseRequest, string $status): void
    {
        $purchaseRequest->update(['status' => $status]);

        app(SyncPipelineStateToStatusAction::class)->execute($purchaseRequest, $status);
    }
}
