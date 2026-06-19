<?php

namespace App\Http\Controllers;

use App\Actions\PipelineDashboard\GetPipelineDashboardDataAction;
use App\Http\Controllers\Concerns\ResolvesBranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PipelineDashboardController extends Controller
{
    use ResolvesBranchScope;

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

        $filters['branch_id'] = $this->resolveBranchFromRequest($request);

        $data = $action->execute($filters);

        return response()->json($data);
    }
}
