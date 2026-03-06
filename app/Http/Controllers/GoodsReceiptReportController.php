<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ExportGoodsReceiptReportAction;
use App\Actions\Reports\IndexGoodsReceiptReportAction;
use App\Http\Requests\Reports\ExportGoodsReceiptReportRequest;
use App\Http\Requests\Reports\IndexGoodsReceiptReportRequest;
use App\Http\Resources\Reports\GoodsReceiptReportCollection;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class GoodsReceiptReportController extends Controller
{
    public function index(
        IndexGoodsReceiptReportRequest $request,
        IndexGoodsReceiptReportAction $action
    ): Response|GoodsReceiptReportCollection {
        $rows = $action->execute($request);

        if ($request->wantsJson()) {
            return new GoodsReceiptReportCollection($rows);
        }

        return Inertia::render('reports/goods-receipt/index', [
            'rows' => new GoodsReceiptReportCollection($rows),
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
        ExportGoodsReceiptReportRequest $request,
        ExportGoodsReceiptReportAction $action
    ): JsonResponse {
        return $action->execute($request);
    }
}
