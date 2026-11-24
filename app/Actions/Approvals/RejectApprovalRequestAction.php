<?php

namespace App\Actions\Approvals;

use App\Events\ApprovalRequestRejected;
use App\Exceptions\ConcurrentApprovalStepException;
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
            $resolver = app(ResolveApprovalStepActorAction::class);

            $locked = ApprovalRequestStep::query()->whereKey($currentStep->getKey())->lockForUpdate()->first();

            if (! $locked instanceof ApprovalRequestStep || $locked->status !== 'pending') {
                throw new ConcurrentApprovalStepException;
            }

            $currentStep->update([
                'status' => 'rejected',
                'acted_by' => $userId,
                'delegated_from' => $resolver->delegatorFor($currentStep, $userId),
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

            DB::afterCommit(fn () => ApprovalRequestRejected::dispatch($approvalRequest, $userId, $comments));
        });
    }
}
