<?php

namespace App\Actions\Approvals;

use App\Models\ApprovalAuditLog;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestStep;
use Illuminate\Support\Facades\DB;

class RejectApprovalRequestAction
{
    public function execute(
        ApprovalRequest $approvalRequest,
        ApprovalRequestStep $currentStep,
        int $userId,
        string $comments,
    ): void {
        DB::transaction(function () use ($approvalRequest, $currentStep, $userId, $comments): void {
            $currentStep->update([
                'status' => 'rejected',
                'acted_by' => $userId,
                'action' => 'reject',
                'comments' => $comments,
                'acted_at' => now(),
            ]);

            $approvalRequest->update([
                'status' => 'rejected',
                'completed_at' => now(),
            ]);

            ApprovalAuditLog::create([
                'approval_request_id' => $approvalRequest->id,
                'approvable_type' => $approvalRequest->approvable_type,
                'approvable_id' => $approvalRequest->approvable_id,
                'event' => 'step_rejected',
                'actor_user_id' => $userId,
                'step_order' => $currentStep->step_order,
                'metadata' => json_encode(['comments' => $comments]),
            ]);
        });
    }
}
