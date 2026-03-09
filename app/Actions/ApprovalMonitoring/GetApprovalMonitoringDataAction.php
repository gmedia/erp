<?php

namespace App\Actions\ApprovalMonitoring;

use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestStep;
use Carbon\Carbon;

class GetApprovalMonitoringDataAction
{
    public function execute(array $filters): array
    {
        $documentType = $filters['document_type'] ?? null;
        $status = $filters['status'] ?? null;
        $approverId = $filters['approver_id'] ?? null;
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        $query = ApprovalRequest::with(['approvable', 'submitter', 'flow']);

        if ($documentType) {
            $query->where('approvable_type', $documentType);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($startDate) {
            $query->where('submitted_at', '>=', Carbon::parse($startDate)->startOfDay());
        }

        if ($endDate) {
            $query->where('submitted_at', '<=', Carbon::parse($endDate)->endOfDay());
        }

        if ($approverId) {
            $query->whereHas('steps', function ($q) use ($approverId) {
                $q->where('acted_by', $approverId)
                    ->orWhereHas('flowStep', function ($fs) use ($approverId) {
                        $fs->where('approver_user_id', $approverId);
                    });
            });
        }

        $allRequests = $query->get();

        // Summary Calculations
        $totalPending = $allRequests->whereIn('status', ['pending', 'in_progress'])->count();

        $today = Carbon::today();
        $approvedToday = $allRequests->where('status', 'approved')
            ->filter(function ($req) use ($today) {
                return $req->completed_at && $req->completed_at->isSameDay($today);
            })->count();

        $rejectedToday = $allRequests->where('status', 'rejected')
            ->filter(function ($req) use ($today) {
                return $req->completed_at && $req->completed_at->isSameDay($today);
            })->count();

        // Average processing time (hours) for completed requests
        $completedRequests = $allRequests->whereNotNull('completed_at')->whereNotNull('submitted_at');
        $avgProcessingTimeHours = 0;
        if ($completedRequests->count() > 0) {
            $totalHours = $completedRequests->sum(function ($req) {
                return $req->submitted_at->diffInHours($req->completed_at);
            });
            $avgProcessingTimeHours = round($totalHours / $completedRequests->count(), 1);
        }

        // Fetch Overdue Approvals
        // Looking at pending request steps where due_at is past
        $overdueQuery = ApprovalRequestStep::with(['request.approvable', 'request.submitter', 'flowStep'])
            ->where('status', 'pending')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now());

        if ($documentType) {
            $overdueQuery->whereHas('request', function ($q) use ($documentType) {
                $q->where('approvable_type', $documentType);
            });
        }

        if ($startDate) {
            $overdueQuery->whereHas('request', function ($q) use ($startDate) {
                $q->where('submitted_at', '>=', Carbon::parse($startDate)->startOfDay());
            });
        }

        if ($endDate) {
            $overdueQuery->whereHas('request', function ($q) use ($endDate) {
                $q->where('submitted_at', '<=', Carbon::parse($endDate)->endOfDay());
            });
        }

        if ($approverId) {
            $overdueQuery->whereHas('flowStep', function ($fs) use ($approverId) {
                $fs->where('approver_user_id', $approverId);
            });
        }

        $overdueSteps = $overdueQuery->orderBy('due_at', 'asc')->limit(50)->get();

        $overdueFormatted = $overdueSteps->map(function ($step) {
            $request = $step->request;
            $document = $request->approvable;

            $entityName = $document->name ?? $document->code ?? $document->title ?? $document->reference ?? "ID: {$request->approvable_id}";

            return [
                'id' => $step->id,
                'request_id' => $request->id,
                'document_type' => class_basename($request->approvable_type),
                'document_name' => $entityName,
                'submitter_name' => $request->submitter->name ?? 'Unknown',
                'step_name' => $step->flowStep->name ?? 'Unknown Step',
                'due_at' => $step->due_at->toISOString(),
                'hours_overdue' => Carbon::now()->diffInHours($step->due_at),
            ];
        });

        return [
            'summary' => [
                'total_pending' => $totalPending,
                'approved_today' => $approvedToday,
                'rejected_today' => $rejectedToday,
                'avg_processing_time_hours' => $avgProcessingTimeHours,
            ],
            'overdue_approvals' => $overdueFormatted,
        ];
    }
}
