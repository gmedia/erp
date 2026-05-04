<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ExportApPaymentHistoryReportAction;
use App\Actions\Reports\IndexApPaymentHistoryReportAction;
use App\Http\Requests\Reports\ExportApPaymentHistoryReportRequest;
use App\Http\Requests\Reports\IndexApPaymentHistoryReportRequest;
use App\Http\Resources\Reports\ApPaymentHistoryReportCollection;
use Illuminate\Http\JsonResponse;

class ApPaymentHistoryReportController extends Controller
{
    public function index(
        IndexApPaymentHistoryReportRequest $request,
        IndexApPaymentHistoryReportAction $action
    ): ApPaymentHistoryReportCollection {
        $rows = $action->execute($request);

        return new ApPaymentHistoryReportCollection($rows);
    }

    public function export(
        ExportApPaymentHistoryReportRequest $request,
        ExportApPaymentHistoryReportAction $action
    ): JsonResponse {
        return $action->execute($request);
    }
}
