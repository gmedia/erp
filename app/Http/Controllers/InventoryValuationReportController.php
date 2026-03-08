<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ExportInventoryValuationReportAction;
use App\Actions\Reports\IndexInventoryValuationReportAction;
use App\Http\Requests\Reports\ExportInventoryValuationReportRequest;
use App\Http\Requests\Reports\IndexInventoryValuationReportRequest;
use App\Http\Resources\Reports\InventoryValuationReportCollection;
use Illuminate\Http\JsonResponse;

class InventoryValuationReportController extends Controller
{
    public function index(
        IndexInventoryValuationReportRequest $request,
        IndexInventoryValuationReportAction $action
    ): InventoryValuationReportCollection {
        $valuations = $action->execute($request);

        return new InventoryValuationReportCollection($valuations);
    }

    public function export(
        ExportInventoryValuationReportRequest $request,
        ExportInventoryValuationReportAction $action
    ): JsonResponse {
        return $action->execute($request);
    }
}
