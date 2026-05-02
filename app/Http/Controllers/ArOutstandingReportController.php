<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ExportArOutstandingReportAction;
use App\Actions\Reports\IndexArOutstandingReportAction;
use App\Http\Requests\Reports\ExportArOutstandingReportRequest;
use App\Http\Requests\Reports\IndexArOutstandingReportRequest;
use App\Http\Resources\Reports\ArOutstandingReportCollection;
use Illuminate\Http\JsonResponse;

class ArOutstandingReportController extends Controller
{
    public function index(
        IndexArOutstandingReportRequest $request,
        IndexArOutstandingReportAction $action
    ): ArOutstandingReportCollection {
        $rows = $action->execute($request);

        return new ArOutstandingReportCollection($rows);
    }

    public function export(
        ExportArOutstandingReportRequest $request,
        ExportArOutstandingReportAction $action
    ): JsonResponse {
        return $action->execute($request);
    }
}
