<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ExportPurchaseOrderStatusReportAction;
use App\Actions\Reports\IndexPurchaseOrderStatusReportAction;
use App\Http\Requests\Reports\ExportPurchaseOrderStatusReportRequest;
use App\Http\Requests\Reports\IndexPurchaseOrderStatusReportRequest;
use App\Http\Resources\Reports\PurchaseOrderStatusReportCollection;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderStatusReportController extends Controller
{
    public function index(
        IndexPurchaseOrderStatusReportRequest $request,
        IndexPurchaseOrderStatusReportAction $action
    ): Response|PurchaseOrderStatusReportCollection {
        $rows = $action->execute($request);

        if ($request->wantsJson()) {
            return new PurchaseOrderStatusReportCollection($rows);
        }

        return Inertia::render('reports/purchase-order-status/index', [
            'rows' => new PurchaseOrderStatusReportCollection($rows),
            'filters' => $request->only([
                'search',
                'supplier_id',
                'warehouse_id',
                'product_id',
                'status',
                'status_category',
                'start_date',
                'end_date',
                'sort_by',
                'sort_direction',
                'per_page',
            ]),
        ]);
    }

    public function export(
        ExportPurchaseOrderStatusReportRequest $request,
        ExportPurchaseOrderStatusReportAction $action
    ): JsonResponse {
        return $action->execute($request);
    }
}
