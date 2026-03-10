<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ExportPurchaseOrderStatusReportAction;
use App\Actions\Reports\IndexPurchaseOrderStatusReportAction;
use App\Http\Requests\Reports\ExportPurchaseOrderStatusReportRequest;
use App\Http\Requests\Reports\IndexPurchaseOrderStatusReportRequest;
use App\Http\Resources\Reports\PurchaseOrderStatusReportCollection;
use Illuminate\Http\JsonResponse;

class PurchaseOrderStatusReportController extends Controller
{
    public function index(
        IndexPurchaseOrderStatusReportRequest $request,
        IndexPurchaseOrderStatusReportAction $action
    ): PurchaseOrderStatusReportCollection {
        $rows = $action->execute($request);

        return new PurchaseOrderStatusReportCollection($rows);
    }

    public function export(
        ExportPurchaseOrderStatusReportRequest $request,
        ExportPurchaseOrderStatusReportAction $action
    ): JsonResponse {
        return $action->execute($request);
    }
}
