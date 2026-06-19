<?php

namespace App\Http\Controllers;

use App\Actions\ApprovalMonitoring\GetApprovalMonitoringDataAction;
use App\Http\Controllers\Concerns\ResolvesBranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalMonitoringController extends Controller
{
    use ResolvesBranchScope;

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

        $filters['branch_id'] = $this->resolveBranchFromRequest($request);

        $data = $action->execute($filters);

        return response()->json($data);
    }
}
