<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ExportStockAdjustmentReportAction;
use App\Actions\Reports\IndexStockAdjustmentReportAction;
use App\Http\Requests\Reports\ExportStockAdjustmentReportRequest;
use App\Http\Requests\Reports\IndexStockAdjustmentReportRequest;
use App\Http\Resources\Reports\StockAdjustmentReportCollection;
use Illuminate\Http\JsonResponse;

class StockAdjustmentReportController extends Controller
{
    public function index(
        IndexStockAdjustmentReportRequest $request,
        IndexStockAdjustmentReportAction $action
    ): StockAdjustmentReportCollection {
        $rows = $action->execute($request);

        return new StockAdjustmentReportCollection($rows);
    }

    public function export(
        ExportStockAdjustmentReportRequest $request,
        ExportStockAdjustmentReportAction $action
    ): JsonResponse {
        return $action->execute($request);
    }
}
