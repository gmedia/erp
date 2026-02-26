<?php

namespace App\Http\Controllers;

use App\Actions\PipelineDashboard\GetPipelineDashboardDataAction;
use App\Models\Pipeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PipelineDashboardController extends Controller
{
    /**
     * Display the Pipeline Dashboard page.
     */
    public function index(): Response
    {
        $pipelines = Pipeline::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'entity_type']);

        return Inertia::render('pipeline-dashboard/index', [
            'pipelines' => $pipelines
        ]);
    }

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
