<?php

namespace App\Listeners;

use App\Actions\EntityStates\SyncPipelineStateToStatusAction;
use App\Events\ApprovalRequestApproved;
use App\Events\ApprovalRequestRejected;
use App\Models\PurchaseOrder;

class MirrorPurchaseOrderApprovalStatus
{
    public function handle(ApprovalRequestApproved|ApprovalRequestRejected $event): void
    {
        $approval = $event->approvalRequest;

        if ($approval->approvable_type !== PurchaseOrder::class) {
            return;
        }

        /** @var PurchaseOrder|null $purchaseOrder */
        $purchaseOrder = PurchaseOrder::find($approval->approvable_id);

        if (! $purchaseOrder) {
            return;
        }

        if ($event instanceof ApprovalRequestRejected) {
            $this->mirror($purchaseOrder, 'rejected');

            return;
        }

        if (! $event->final) {
            return;
        }

        $finalApproverId = $approval->steps()
            ->where('status', 'approved')
            ->orderByDesc('step_order')
            ->value('acted_by');

        $purchaseOrder->update([
            'approved_at' => $approval->completed_at ?? now(),
            'approved_by' => $finalApproverId ?? $event->actorUserId,
        ]);

        $this->mirror($purchaseOrder, 'confirmed');
    }

    private function mirror(PurchaseOrder $purchaseOrder, string $status): void
    {
        $purchaseOrder->update(['status' => $status]);

        app(SyncPipelineStateToStatusAction::class)->execute($purchaseOrder, $status);
    }
}
