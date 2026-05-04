<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ExportApAgingReportAction;
use App\Actions\Reports\IndexApAgingReportAction;
use App\Http\Requests\Reports\ExportApAgingReportRequest;
use App\Http\Requests\Reports\IndexApAgingReportRequest;
use App\Http\Resources\Reports\ApAgingReportCollection;
use Illuminate\Http\JsonResponse;

class ApAgingReportController extends Controller
{
    public function index(
        IndexApAgingReportRequest $request,
        IndexApAgingReportAction $action
    ): ApAgingReportCollection {
        $rows = $action->execute($request);

        return new ApAgingReportCollection($rows);
    }

    public function export(
        ExportApAgingReportRequest $request,
        ExportApAgingReportAction $action
    ): JsonResponse {
        return $action->execute($request);
    }
}
