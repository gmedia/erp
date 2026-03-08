<?php

namespace App\Http\Controllers;

use App\Actions\PipelineDashboard\GetPipelineDashboardDataAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PipelineDashboardController extends Controller
{
    /**
     * Get aggregate data for the dashboard.
     */
    public function getData(Request $request, GetPipelineDashboardDataAction $action): JsonResponse
    {
        $filters = $request->only([
            'pipeline_id',
            'entity_type',
            'stale_days',
        ]);

        $data = $action->execute($filters);

        return response()->json($data);
    }
}
