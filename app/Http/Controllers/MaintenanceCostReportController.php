<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ExportMaintenanceCostReportAction;
use App\Actions\Reports\IndexMaintenanceCostReportAction;
use App\Http\Requests\Reports\ExportMaintenanceCostRequest;
use App\Http\Requests\Reports\IndexMaintenanceCostRequest;
use App\Http\Resources\Reports\MaintenanceCostCollection;
use Illuminate\Http\JsonResponse;

class MaintenanceCostReportController extends Controller
{
    /**
     * Display the Maintenance Cost report.
     */
    public function index(
        IndexMaintenanceCostRequest $request,
        IndexMaintenanceCostReportAction $action
    ): MaintenanceCostCollection {
        $maintenances = $action->execute($request);

        return new MaintenanceCostCollection($maintenances);
    }

    /**
     * Export the Maintenance Cost report to Excel/CSV.
     */
    public function export(
        ExportMaintenanceCostRequest $request,
        ExportMaintenanceCostReportAction $action
    ): JsonResponse {
        return $action->execute($request);
    }
}
