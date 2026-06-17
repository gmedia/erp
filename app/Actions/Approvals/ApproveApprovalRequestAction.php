<?php

namespace App\Actions\Approvals;

use App\Models\ApprovalAuditLog;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestStep;
use Illuminate\Support\Facades\DB;

class ApproveApprovalRequestAction
{
    public function execute(
        ApprovalRequest $approvalRequest,
        ApprovalRequestStep $currentStep,
        int $userId,
        ?string $comments,
    ): void {
        DB::transaction(function () use ($approvalRequest, $currentStep, $userId, $comments): void {
            $currentStep->update([
                'status' => 'approved',
                'acted_by' => $userId,
                'action' => 'approve',
                'comments' => $comments,
                'acted_at' => now(),
            ]);

            ApprovalAuditLog::create([
                'approval_request_id' => $approvalRequest->id,
                'approvable_type' => $approvalRequest->approvable_type,
                'approvable_id' => $approvalRequest->approvable_id,
                'event' => 'step_approved',
                'actor_user_id' => $userId,
                'step_order' => $currentStep->step_order,
                'metadata' => json_encode(['comments' => $comments]),
            ]);

            /** @var ApprovalRequestStep|null $nextStep */
            $nextStep = $approvalRequest->steps()
                ->where('step_order', '>', $currentStep->step_order)
                ->orderBy('step_order', 'asc')
                ->first();

            if ($nextStep instanceof ApprovalRequestStep) {
                $approvalRequest->update([
                    'current_step_order' => $nextStep->step_order,
                    'status' => 'in_progress',
                ]);

                return;
            }

            $approvalRequest->update([
                'status' => 'approved',
                'completed_at' => now(),
            ]);

            ApprovalAuditLog::create([
                'approval_request_id' => $approvalRequest->id,
                'approvable_type' => $approvalRequest->approvable_type,
                'approvable_id' => $approvalRequest->approvable_id,
                'event' => 'completed',
                'actor_user_id' => $userId,
                'metadata' => json_encode([]),
            ]);
        });
    }
}
