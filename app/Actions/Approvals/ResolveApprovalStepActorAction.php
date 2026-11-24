<?php

namespace App\Actions\Approvals;

use App\Exceptions\ConcurrentApprovalStepException;
use App\Models\ApprovalDelegation;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestStep;
use Illuminate\Database\Eloquent\Builder;

/**
 * Decides whether a user may act on the current pending step of a request.
 *
 * Rules:
 * - The submitter may never act on their own request, even as a delegate.
 * - A direct approver (approver_type=user, approver_user_id) may act.
 * - An active delegate may act on a step whose approver delegated to them,
 *   within the delegation date range and document scope. Delegations do not chain.
 */
class ResolveApprovalStepActorAction
{
    /**
     * Returns the step the user may act on, or null when not allowed.
     */
    public function execute(ApprovalRequest $approvalRequest, int $userId): ?ApprovalRequestStep
    {
        if ((int) $approvalRequest->submitted_by === $userId) {
            return null;
        }

        /** @var ApprovalRequestStep|null $step */
        $step = $approvalRequest->steps()
            ->where('status', 'pending')
            ->where('step_order', $approvalRequest->current_step_order)
            ->whereHas('flowStep', fn (Builder $flowStep) => $flowStep
                ->where('approver_type', 'user')
                ->where(function (Builder $approver) use ($userId, $approvalRequest): void {
                    $approver->where('approver_user_id', $userId)
                        ->orWhereIn('approver_user_id', $this->delegatorIdsFor($userId, $approvalRequest));
                }))
            ->lockForUpdate()
            ->first();

        return $step;
    }

    /**
     * Locks the step for a decision and returns it, or throws when it is no longer pending.
     *
     * @throws ConcurrentApprovalStepException
     */
    public function lockPendingStep(ApprovalRequestStep $currentStep): ApprovalRequestStep
    {
        $locked = ApprovalRequestStep::query()->whereKey($currentStep->getKey())->lockForUpdate()->first();

        if (! $locked instanceof ApprovalRequestStep || $locked->status !== 'pending') {
            throw new ConcurrentApprovalStepException;
        }

        return $locked;
    }

    /**
     * Locks the step and records a decision ('approve' or 'reject') by $userId.
     * Returns the attributes written to the step.
     *
     * @return array<string, mixed>
     *
     * @throws ConcurrentApprovalStepException
     */
    public function recordDecision(
        ApprovalRequestStep $currentStep,
        int $userId,
        string $decision,
        ?string $comments,
    ): array {
        $this->lockPendingStep($currentStep);

        $attributes = [
            'status' => $decision === 'approve' ? 'approved' : 'rejected',
            'acted_by' => $userId,
            'delegated_from' => $this->delegatorFor($currentStep, $userId),
            'action' => $decision,
            'comments' => $comments,
            'acted_at' => now(),
        ];

        $currentStep->update($attributes);

        return $attributes;
    }

    /**
     * The approver who delegated this step to $userId, or null when the direct approver acts.
     */
    public function delegatorFor(ApprovalRequestStep $step, int $userId): ?int
    {
        $approverId = $step->flowStep->approver_user_id;

        return $approverId !== null && (int) $approverId !== $userId ? (int) $approverId : null;
    }

    /**
     * Delegators whose step the user may currently act on for this request.
     *
     * @return list<int>
     */
    private function delegatorIdsFor(int $userId, ApprovalRequest $approvalRequest): array
    {
        return ApprovalDelegation::query()
            ->activeForDelegate($userId)
            ->coveringApprovable($approvalRequest->approvable_type)
            ->pluck('delegator_user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
