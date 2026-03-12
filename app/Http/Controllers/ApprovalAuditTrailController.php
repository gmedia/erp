<?php

namespace App\Http\Controllers;

use App\Actions\ApprovalAuditTrail\ExportApprovalAuditTrailAction;
use App\Actions\ApprovalAuditTrail\IndexApprovalAuditTrailAction;
use App\Http\Requests\ApprovalAuditTrail\ExportApprovalAuditTrailRequest;
use App\Http\Requests\ApprovalAuditTrail\IndexApprovalAuditTrailRequest;
use App\Http\Resources\ApprovalAuditTrail\ApprovalAuditTrailCollection;
use Illuminate\Http\JsonResponse;

class ApprovalAuditTrailController extends Controller
{
    /**
     * Display the Approval Audit Trail page.
     */
    public function index(
        IndexApprovalAuditTrailRequest $request,
        IndexApprovalAuditTrailAction $action
    ): ApprovalAuditTrailCollection
    {
        $logs = $action->execute($request);

        return new ApprovalAuditTrailCollection($logs);
    }

    /**
     * Export the Approval Audit Trail to Excel/CSV.
     */
    public function export(ExportApprovalAuditTrailRequest $request, ExportApprovalAuditTrailAction $action): JsonResponse
    {
        return $action->execute($request);
    }
}
