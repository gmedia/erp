<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestStep;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\ApprovalAuditLog;

class MyApprovalController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // A step is waiting for the current user if:
        // 1. the step is pending
        // 2. the step's approver_type is 'user' and approver_user_id == $userId
        // (Role and department logic omitted for MVP unless needed)

        $pending = ApprovalRequestStep::with(['request.approvable', 'request.submitter', 'flowStep'])
            ->where('status', 'pending')
            ->whereHas('request', function ($q) {
                $q->whereIn('status', ['pending', 'in_progress']);
            })
            ->whereHas('flowStep', function ($q) use ($userId) {
                $q->where('approver_type', 'user')
                  ->where('approver_user_id', $userId);
            })
            ->get();

        $approvedByMe = ApprovalRequestStep::with(['request.approvable', 'request.submitter', 'flowStep'])
            ->where('status', 'approved')
            ->where('acted_by', $userId)
            ->get();

        $rejectedByMe = ApprovalRequestStep::with(['request.approvable', 'request.submitter', 'flowStep'])
            ->where('status', 'rejected')
            ->where('acted_by', $userId)
            ->get();

        $all = ApprovalRequestStep::with(['request.approvable', 'request.submitter', 'flowStep'])
            ->where('acted_by', $userId)
            ->orWhereHas('flowStep', function ($q) use ($userId) {
                $q->where('approver_type', 'user')
                  ->where('approver_user_id', $userId);
            })
            ->get();

        return Inertia::render('my-approvals/index', [
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
        
        DB::transaction(function () use ($approvalRequest, $userId, $request) {
            $currentStep = $approvalRequest->steps()
                ->where('status', 'pending')
                ->where('step_order', $approvalRequest->current_step_order)
                ->firstOrFail();

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

        return back()->with('success', 'Request approved successfully.');
    }

    public function reject(Request $request, ApprovalRequest $approvalRequest)
    {
        $request->validate(['comments' => 'required|string']);
        
        $userId = Auth::id();

        DB::transaction(function () use ($approvalRequest, $userId, $request) {
            $currentStep = $approvalRequest->steps()
                ->where('status', 'pending')
                ->where('step_order', $approvalRequest->current_step_order)
                ->firstOrFail();

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

        return back()->with('success', 'Request rejected.');
    }
}
