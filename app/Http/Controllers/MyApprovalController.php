<?php

namespace App\Http\Controllers;

use App\Actions\Approvals\ApproveApprovalRequestAction;
use App\Actions\Approvals\RejectApprovalRequestAction;
use App\Http\Requests\MyApprovals\ApproveMyApprovalRequest;
use App\Http\Requests\MyApprovals\RejectMyApprovalRequest;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestStep;
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
            abort(403, 'You are not authorized to act on this approval step.');
        }

        $action->execute($approvalRequest, $currentStep, $userId, $request->validated('comments'));

        return response()->json(['message' => 'Request approved successfully.']);
    }

    public function reject(
        RejectMyApprovalRequest $request,
        ApprovalRequest $approvalRequest,
        RejectApprovalRequestAction $action,
    ): JsonResponse {
        $userId = Auth::id();
        $currentStep = $this->findCurrentPendingAssignedStep($approvalRequest, $userId);

        if (! $currentStep instanceof ApprovalRequestStep) {
            abort(403, 'You are not authorized to act on this approval step.');
        }

        $action->execute($approvalRequest, $currentStep, $userId, $request->validated('comments'));

        return response()->json(['message' => 'Request rejected.']);
    }

    private function findCurrentPendingAssignedStep(
        ApprovalRequest $approvalRequest,
        int $userId,
    ): ?ApprovalRequestStep {
        /** @var ApprovalRequestStep|null $step */
        $step = $approvalRequest->steps()
            ->where('status', 'pending')
            ->where('step_order', $approvalRequest->current_step_order)
            ->whereHas('flowStep', function ($flowStepQuery) use ($userId) {
                $flowStepQuery->where('approver_type', 'user')
                    ->where('approver_user_id', $userId);
            })
            ->first();

        return $step;
    }
}
