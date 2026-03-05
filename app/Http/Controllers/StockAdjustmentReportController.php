<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ExportStockAdjustmentReportAction;
use App\Actions\Reports\IndexStockAdjustmentReportAction;
use App\Http\Requests\Reports\ExportStockAdjustmentReportRequest;
use App\Http\Requests\Reports\IndexStockAdjustmentReportRequest;
use App\Http\Resources\Reports\StockAdjustmentReportCollection;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class StockAdjustmentReportController extends Controller
{
    public function index(
        IndexStockAdjustmentReportRequest $request,
        IndexStockAdjustmentReportAction $action
    ): Response|StockAdjustmentReportCollection {
        $rows = $action->execute($request);

        if ($request->wantsJson()) {
            return new StockAdjustmentReportCollection($rows);
        }

        return Inertia::render('reports/stock-adjustment/index', [
            'rows' => new StockAdjustmentReportCollection($rows),
            'filters' => $request->only([
                'search',
                'warehouse_id',
                'branch_id',
                'adjustment_type',
                'status',
                'start_date',
                'end_date',
                'sort_by',
                'sort_direction',
                'per_page',
            ]),
        ]);
    }

    public function export(
        ExportStockAdjustmentReportRequest $request,
        ExportStockAdjustmentReportAction $action
    ): JsonResponse {
        return $action->execute($request);
    }
}
