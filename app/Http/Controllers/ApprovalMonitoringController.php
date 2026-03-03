<?php

namespace App\Http\Controllers;

use App\Actions\ApprovalMonitoring\GetApprovalMonitoringDataAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalMonitoringController extends Controller
{
    /**
     * Display the Approval Monitoring dashboard.
     */
    public function index(): Response
    {
        return Inertia::render('approval-monitoring/index');
    }

    /**
     * Get aggregate and overdue data for the dashboard.
     */
    public function getData(Request $request, GetApprovalMonitoringDataAction $action): JsonResponse
    {
        $filters = $request->only([
            'document_type',
            'status',
            'approver_id',
            'start_date',
            'end_date',
        ]);

        $data = $action->execute($filters);

        return response()->json($data);
    }
}
