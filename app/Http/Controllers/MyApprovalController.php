<?php

namespace App\Http\Controllers;

use App\Actions\Approvals\ApproveApprovalRequestAction;
use App\Actions\Approvals\RejectApprovalRequestAction;
use App\Actions\Approvals\ResolveApprovalStepActorAction;
use App\Http\Requests\MyApprovals\ApproveMyApprovalRequest;
use App\Http\Requests\MyApprovals\RejectMyApprovalRequest;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestStep;
use App\Support\DocumentNumberResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class MyApprovalController extends Controller
{
    public function index(): JsonResponse
    {
        $userId = Auth::id();

        $inboxQuery = fn () => ApprovalRequestStep::with([
            'request.approvable',
            'request.submitter',
            'flowStep',
        ])->orderByDesc('updated_at')->orderByDesc('id');

        $pending = $inboxQuery()
            ->pendingInboxForUser($userId)
            ->get();

        $approvedByMe = $inboxQuery()
            ->where('status', 'approved')
            ->where('acted_by', $userId)
            ->get();

        $rejectedByMe = $inboxQuery()
            ->where('status', 'rejected')
            ->where('acted_by', $userId)
            ->get();

        $all = $inboxQuery()
            ->where(function ($query) use ($userId) {
                $query->where('acted_by', $userId)
                    ->orWhere(function ($pendingQuery) use ($userId) {
                        $pendingQuery->pendingInboxForUser($userId);
                    });
            })
            ->get();

        $resolver = app(DocumentNumberResolver::class);
        $lists = [$pending, $approvedByMe, $rejectedByMe, $all];

        // Flag steps the viewer acts on as a delegate (approver differs from viewer).
        $resolveActor = app(ResolveApprovalStepActorAction::class);
        foreach ($lists as $steps) {
            $steps->each(function (ApprovalRequestStep $step) use ($resolveActor, $userId): void {
                $step->setAttribute('is_delegated', $resolveActor->delegatorFor($step, $userId) !== null);
            });
        }

        // Resolve every document number up front: one query per approvable type,
        // not one per step (avoids N+1 on the inbox lists).
        $resolver->preload(
            collect($lists)
                ->flatten(1)
                ->map(fn (ApprovalRequestStep $step) => [
                    $step->request->approvable_type,
                    $step->request->approvable_id,
                ]),
        );

        foreach ($lists as $steps) {
            $steps->each(function (ApprovalRequestStep $step) use ($resolver): void {
                $step->request->setAttribute('document_number', $resolver->resolve(
                    $step->request->approvable_type,
                    $step->request->approvable_id,
                ));
            });
        }

        return response()->json([
            'pending' => $pending,
            'approved' => $approvedByMe,
            'rejected' => $rejectedByMe,
            'all' => $all,
        ]);
    }

    public function approve(
        ApproveMyApprovalRequest $request,
        ApprovalRequest $approvalRequest,
        ApproveApprovalRequestAction $action,
    ): JsonResponse {
        $userId = Auth::id();
        $currentStep = $this->findCurrentPendingAssignedStep($approvalRequest, $userId);

        if (! $currentStep instanceof ApprovalRequestStep) {
            abort(403, __('my_approval_api.not_authorized_for_step'));
        }

        $action->execute($approvalRequest, $currentStep, $userId, $request->validated('comments'));

        return response()->json(['message' => __('my_approval_api.approved')]);
    }

    public function reject(
        RejectMyApprovalRequest $request,
        ApprovalRequest $approvalRequest,
        RejectApprovalRequestAction $action,
    ): JsonResponse {
        $userId = Auth::id();
        $currentStep = $this->findCurrentPendingAssignedStep($approvalRequest, $userId);

        if (! $currentStep instanceof ApprovalRequestStep) {
            abort(403, __('my_approval_api.not_authorized_for_step'));
        }

        $action->execute($approvalRequest, $currentStep, $userId, $request->validated('comments'));

        return response()->json(['message' => __('my_approval_api.rejected')]);
    }

    private function findCurrentPendingAssignedStep(
        ApprovalRequest $approvalRequest,
        int $userId,
    ): ?ApprovalRequestStep {
        return app(ResolveApprovalStepActorAction::class)->execute($approvalRequest, $userId);
    }
}
