<?php

namespace App\Http\Controllers;

use App\Actions\ApprovalAuditTrail\ExportApprovalAuditTrailAction;
use App\Actions\ApprovalAuditTrail\IndexApprovalAuditTrailAction;
use App\Http\Requests\ApprovalAuditTrail\ExportApprovalAuditTrailRequest;
use App\Http\Requests\ApprovalAuditTrail\IndexApprovalAuditTrailRequest;
use App\Http\Resources\ApprovalAuditTrail\ApprovalAuditTrailCollection;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalAuditTrailController extends Controller
{
    /**
     * Display the Approval Audit Trail page.
     */
    public function index(IndexApprovalAuditTrailRequest $request, IndexApprovalAuditTrailAction $action): Response|ApprovalAuditTrailCollection
    {
        $logs = $action->execute($request);

        if ($request->wantsJson()) {
            return new ApprovalAuditTrailCollection($logs);
        }

        return Inertia::render('approval-audit-trail/index', [
            'logs' => new ApprovalAuditTrailCollection($logs),
            'filters' => $request->only([
                'search',
                'approvable_type',
                'event',
                'actor_user_id',
                'start_date',
                'end_date',
                'sort_by',
                'sort_direction',
            ]),
        ]);
    }

    /**
     * Export the Approval Audit Trail to Excel/CSV.
     */
    public function export(ExportApprovalAuditTrailRequest $request, ExportApprovalAuditTrailAction $action): JsonResponse
    {
        return $action->execute($request);
    }
}
