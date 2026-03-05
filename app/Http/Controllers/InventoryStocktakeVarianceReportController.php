<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ExportInventoryStocktakeVarianceReportAction;
use App\Actions\Reports\IndexInventoryStocktakeVarianceReportAction;
use App\Http\Requests\Reports\ExportInventoryStocktakeVarianceReportRequest;
use App\Http\Requests\Reports\IndexInventoryStocktakeVarianceReportRequest;
use App\Http\Resources\Reports\InventoryStocktakeVarianceReportCollection;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class InventoryStocktakeVarianceReportController extends Controller
{
    public function index(
        IndexInventoryStocktakeVarianceReportRequest $request,
        IndexInventoryStocktakeVarianceReportAction $action
    ): Response|InventoryStocktakeVarianceReportCollection {
        $rows = $action->execute($request);

        if ($request->wantsJson()) {
            return new InventoryStocktakeVarianceReportCollection($rows);
        }

        return Inertia::render('reports/inventory-stocktake-variance/index', [
            'rows' => new InventoryStocktakeVarianceReportCollection($rows),
            'filters' => $request->only([
                'search',
                'inventory_stocktake_id',
                'product_id',
                'warehouse_id',
                'branch_id',
                'category_id',
                'result',
                'start_date',
                'end_date',
                'sort_by',
                'sort_direction',
                'per_page',
            ]),
        ]);
    }

    public function export(
        ExportInventoryStocktakeVarianceReportRequest $request,
        ExportInventoryStocktakeVarianceReportAction $action
    ): JsonResponse {
        return $action->execute($request);
    }
}
