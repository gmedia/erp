<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ExportGoodsReceiptReportAction;
use App\Actions\Reports\IndexGoodsReceiptReportAction;
use App\Http\Requests\Reports\ExportGoodsReceiptReportRequest;
use App\Http\Requests\Reports\IndexGoodsReceiptReportRequest;
use App\Http\Resources\Reports\GoodsReceiptReportCollection;
use Illuminate\Http\JsonResponse;

class GoodsReceiptReportController extends Controller
{
    public function index(
        IndexGoodsReceiptReportRequest $request,
        IndexGoodsReceiptReportAction $action
    ): GoodsReceiptReportCollection {
        $rows = $action->execute($request);

        return new GoodsReceiptReportCollection($rows);
    }

    public function export(
        ExportGoodsReceiptReportRequest $request,
        ExportGoodsReceiptReportAction $action
    ): JsonResponse {
        return $action->execute($request);
    }
}
