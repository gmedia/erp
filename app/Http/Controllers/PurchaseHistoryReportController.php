<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ExportPurchaseHistoryReportAction;
use App\Actions\Reports\IndexPurchaseHistoryReportAction;
use App\Http\Requests\Reports\ExportPurchaseHistoryReportRequest;
use App\Http\Requests\Reports\IndexPurchaseHistoryReportRequest;
use App\Http\Resources\Reports\PurchaseHistoryReportCollection;
use Illuminate\Http\JsonResponse;

class PurchaseHistoryReportController extends Controller
{
    public function index(
        IndexPurchaseHistoryReportRequest $request,
        IndexPurchaseHistoryReportAction $action
    ): PurchaseHistoryReportCollection {
        $rows = $action->execute($request);

        return new PurchaseHistoryReportCollection($rows);
    }

    public function export(
        ExportPurchaseHistoryReportRequest $request,
        ExportPurchaseHistoryReportAction $action
    ): JsonResponse {
        return $action->execute($request);
    }
}
