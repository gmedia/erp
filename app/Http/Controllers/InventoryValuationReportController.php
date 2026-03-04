<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ExportInventoryValuationReportAction;
use App\Actions\Reports\IndexInventoryValuationReportAction;
use App\Http\Requests\Reports\ExportInventoryValuationReportRequest;
use App\Http\Requests\Reports\IndexInventoryValuationReportRequest;
use App\Http\Resources\Reports\InventoryValuationReportCollection;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class InventoryValuationReportController extends Controller
{
    public function index(
        IndexInventoryValuationReportRequest $request,
        IndexInventoryValuationReportAction $action
    ): Response|InventoryValuationReportCollection {
        $valuations = $action->execute($request);

        if ($request->wantsJson()) {
            return new InventoryValuationReportCollection($valuations);
        }

        return Inertia::render('reports/inventory-valuation/index', [
            'valuations' => new InventoryValuationReportCollection($valuations),
            'filters' => $request->only([
                'search',
                'product_id',
                'warehouse_id',
                'branch_id',
                'category_id',
                'sort_by',
                'sort_direction',
                'per_page',
            ]),
        ]);
    }

    public function export(
        ExportInventoryValuationReportRequest $request,
        ExportInventoryValuationReportAction $action
    ): JsonResponse {
        return $action->execute($request);
    }
}
