<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ExportStockMovementReportAction;
use App\Actions\Reports\IndexStockMovementReportAction;
use App\Http\Requests\Reports\ExportStockMovementReportRequest;
use App\Http\Requests\Reports\IndexStockMovementReportRequest;
use App\Http\Resources\Reports\StockMovementReportCollection;
use Illuminate\Http\JsonResponse;

class StockMovementReportController extends Controller
{
    public function index(
        IndexStockMovementReportRequest $request,
        IndexStockMovementReportAction $action
    ): StockMovementReportCollection {
        $rows = $action->execute($request);

        return new StockMovementReportCollection($rows);
    }

    public function export(
        ExportStockMovementReportRequest $request,
        ExportStockMovementReportAction $action
    ): JsonResponse {
        return $action->execute($request);
    }
}
