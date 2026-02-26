<?php

namespace App\Http\Controllers;

use App\Actions\PipelineAuditTrail\ExportPipelineAuditTrailAction;
use App\Actions\PipelineAuditTrail\IndexPipelineAuditTrailAction;
use App\Http\Requests\PipelineAuditTrail\ExportPipelineAuditTrailRequest;
use App\Http\Requests\PipelineAuditTrail\IndexPipelineAuditTrailRequest;
use App\Http\Resources\PipelineAuditTrail\PipelineAuditTrailCollection;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class PipelineAuditTrailController extends Controller
{
    /**
     * Display the Pipeline Audit Trail page.
     */
    public function index(IndexPipelineAuditTrailRequest $request, IndexPipelineAuditTrailAction $action): Response|PipelineAuditTrailCollection
    {
        $logs = $action->execute($request);

        if ($request->wantsJson()) {
            return new PipelineAuditTrailCollection($logs);
        }

        return Inertia::render('pipeline-audit-trail/index', [
            'logs' => new PipelineAuditTrailCollection($logs),
            'filters' => $request->only([
                'search',
                'entity_type',
                'pipeline_id',
                'from_state_id',
                'to_state_id',
                'performed_by',
                'start_date',
                'end_date',
                'sort_by',
                'sort_direction',
            ]),
        ]);
    }

    /**
     * Export the Pipeline Audit Trail to Excel/CSV.
     */
    public function export(ExportPipelineAuditTrailRequest $request, ExportPipelineAuditTrailAction $action): JsonResponse
    {
        return $action->execute($request);
    }
}
