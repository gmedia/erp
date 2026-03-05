<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ExportStockMovementReportAction;
use App\Actions\Reports\IndexStockMovementReportAction;
use App\Http\Requests\Reports\ExportStockMovementReportRequest;
use App\Http\Requests\Reports\IndexStockMovementReportRequest;
use App\Http\Resources\Reports\StockMovementReportCollection;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class StockMovementReportController extends Controller
{
    public function index(
        IndexStockMovementReportRequest $request,
        IndexStockMovementReportAction $action
    ): Response|StockMovementReportCollection {
        $rows = $action->execute($request);

        if ($request->wantsJson()) {
            return new StockMovementReportCollection($rows);
        }

        return Inertia::render('reports/stock-movement/index', [
            'rows' => new StockMovementReportCollection($rows),
            'filters' => $request->only([
                'search',
                'product_id',
                'warehouse_id',
                'branch_id',
                'category_id',
                'start_date',
                'end_date',
                'sort_by',
                'sort_direction',
                'per_page',
            ]),
        ]);
    }

    public function export(
        ExportStockMovementReportRequest $request,
        ExportStockMovementReportAction $action
    ): JsonResponse {
        return $action->execute($request);
    }
}
