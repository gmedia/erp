<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ExportMaintenanceCostReportAction;
use App\Actions\Reports\IndexMaintenanceCostReportAction;
use App\Http\Requests\Reports\ExportMaintenanceCostRequest;
use App\Http\Requests\Reports\IndexMaintenanceCostRequest;
use App\Http\Resources\Reports\MaintenanceCostCollection;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceCostReportController extends Controller
{
    /**
     * Display the Maintenance Cost report.
     */
    public function index(IndexMaintenanceCostRequest $request, IndexMaintenanceCostReportAction $action): Response|MaintenanceCostCollection
    {
        $maintenances = $action->execute($request);

        if ($request->wantsJson()) {
            return new MaintenanceCostCollection($maintenances);
        }

        return Inertia::render('reports/maintenance-cost/index', [
            'maintenances' => new MaintenanceCostCollection($maintenances),
            'filters' => $request->only([
                'search',
                'asset_category_id',
                'branch_id',
                'supplier_id',
                'maintenance_type',
                'status',
                'start_date',
                'end_date',
                'sort_by',
                'sort_direction',
            ]),
        ]);
    }

    /**
     * Export the Maintenance Cost report to Excel/CSV.
     */
    public function export(ExportMaintenanceCostRequest $request, ExportMaintenanceCostReportAction $action): JsonResponse
    {
        return $action->execute($request);
    }
}
