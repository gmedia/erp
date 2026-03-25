<?php

namespace App\Http\Controllers;

use App\Models\ApprovalAuditLog;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MyApprovalController extends Controller
{
    public function index()
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

    public function approve(Request $request, ApprovalRequest $approvalRequest)
    {
        $request->validate(['comments' => 'nullable|string']);

        $userId = Auth::id();

        $currentStep = $this->findCurrentPendingAssignedStep($approvalRequest, $userId);

        if (! $currentStep instanceof ApprovalRequestStep) {
            abort(403, 'You are not authorized to act on this approval step.');
        }

        DB::transaction(function () use ($approvalRequest, $userId, $request, $currentStep) {
            $currentStep->update([
                'status' => 'approved',
                'acted_by' => $userId,
                'action' => 'approve',
                'comments' => $request->comments,
                'acted_at' => now(),
            ]);

            ApprovalAuditLog::create([
                'approval_request_id' => $approvalRequest->id,
                'approvable_type' => $approvalRequest->approvable_type,
                'approvable_id' => $approvalRequest->approvable_id,
                'event' => 'step_approved',
                'actor_user_id' => $userId,
                'step_order' => $currentStep->step_order,
                'metadata' => json_encode(['comments' => $request->comments]),
            ]);

            // Check if there are more steps
            /** @var \App\Models\ApprovalRequestStep|null $nextStep */
            $nextStep = $approvalRequest->steps()
                ->where('step_order', '>', $currentStep->step_order)
                ->orderBy('step_order', 'asc')
                ->first();

            if ($nextStep) {
                $approvalRequest->update(['current_step_order' => $nextStep->step_order, 'status' => 'in_progress']);
            } else {
                $approvalRequest->update(['status' => 'approved', 'completed_at' => now()]);

                ApprovalAuditLog::create([
                    'approval_request_id' => $approvalRequest->id,
                    'approvable_type' => $approvalRequest->approvable_type,
                    'approvable_id' => $approvalRequest->approvable_id,
                    'event' => 'completed',
                    'actor_user_id' => $userId,
                    'metadata' => json_encode([]),
                ]);
            }
        });

        return response()->json(['message' => 'Request approved successfully.']);
    }

    public function reject(Request $request, ApprovalRequest $approvalRequest)
    {
        $request->validate(['comments' => 'required|string']);

        $userId = Auth::id();

        $currentStep = $this->findCurrentPendingAssignedStep($approvalRequest, $userId);

        if (! $currentStep instanceof ApprovalRequestStep) {
            abort(403, 'You are not authorized to act on this approval step.');
        }

        DB::transaction(function () use ($approvalRequest, $userId, $request, $currentStep) {
            $currentStep->update([
                'status' => 'rejected',
                'acted_by' => $userId,
                'action' => 'reject',
                'comments' => $request->comments,
                'acted_at' => now(),
            ]);

            $approvalRequest->update(['status' => 'rejected', 'completed_at' => now()]);

            ApprovalAuditLog::create([
                'approval_request_id' => $approvalRequest->id,
                'approvable_type' => $approvalRequest->approvable_type,
                'approvable_id' => $approvalRequest->approvable_id,
                'event' => 'step_rejected',
                'actor_user_id' => $userId,
                'step_order' => $currentStep->step_order,
                'metadata' => json_encode(['comments' => $request->comments]),
            ]);
        });

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
