<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ExportApOutstandingReportAction;
use App\Actions\Reports\IndexApOutstandingReportAction;
use App\Http\Requests\Reports\ExportApOutstandingReportRequest;
use App\Http\Requests\Reports\IndexApOutstandingReportRequest;
use App\Http\Resources\Reports\ApOutstandingReportCollection;
use Illuminate\Http\JsonResponse;

class ApOutstandingReportController extends Controller
{
    public function index(
        IndexApOutstandingReportRequest $request,
        IndexApOutstandingReportAction $action
    ): ApOutstandingReportCollection {
        $rows = $action->execute($request);

        return new ApOutstandingReportCollection($rows);
    }

    public function export(
        ExportApOutstandingReportRequest $request,
        ExportApOutstandingReportAction $action
    ): JsonResponse {
        return $action->execute($request);
    }
}
