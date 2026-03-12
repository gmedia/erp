<?php

namespace App\Http\Controllers;

use App\Actions\PipelineAuditTrail\ExportPipelineAuditTrailAction;
use App\Actions\PipelineAuditTrail\IndexPipelineAuditTrailAction;
use App\Http\Requests\PipelineAuditTrail\ExportPipelineAuditTrailRequest;
use App\Http\Requests\PipelineAuditTrail\IndexPipelineAuditTrailRequest;
use App\Http\Resources\PipelineAuditTrail\PipelineAuditTrailCollection;
use Illuminate\Http\JsonResponse;

class PipelineAuditTrailController extends Controller
{
    /**
     * Display the Pipeline Audit Trail page.
     */
    public function index(
        IndexPipelineAuditTrailRequest $request,
        IndexPipelineAuditTrailAction $action
    ): PipelineAuditTrailCollection
    {
        $logs = $action->execute($request);

        return new PipelineAuditTrailCollection($logs);
    }

    /**
     * Export the Pipeline Audit Trail to Excel/CSV.
     */
    public function export(
        ExportPipelineAuditTrailRequest $request,
        ExportPipelineAuditTrailAction $action
    ): JsonResponse
    {
        return $action->execute($request);
    }
}
