<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ExportPurchaseHistoryReportAction;
use App\Actions\Reports\IndexPurchaseHistoryReportAction;
use App\Http\Requests\Reports\ExportPurchaseHistoryReportRequest;
use App\Http\Requests\Reports\IndexPurchaseHistoryReportRequest;
use App\Http\Resources\Reports\PurchaseHistoryReportCollection;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseHistoryReportController extends Controller
{
    public function index(
        IndexPurchaseHistoryReportRequest $request,
        IndexPurchaseHistoryReportAction $action
    ): Response|PurchaseHistoryReportCollection {
        $rows = $action->execute($request);

        if ($request->wantsJson()) {
            return new PurchaseHistoryReportCollection($rows);
        }

        return Inertia::render('reports/purchase-history/index', [
            'rows' => new PurchaseHistoryReportCollection($rows),
            'filters' => $request->only([
                'search',
                'supplier_id',
                'warehouse_id',
                'product_id',
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
        ExportPurchaseHistoryReportRequest $request,
        ExportPurchaseHistoryReportAction $action
    ): JsonResponse {
        return $action->execute($request);
    }
}
