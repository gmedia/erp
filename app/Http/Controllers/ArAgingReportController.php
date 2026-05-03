<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ExportArAgingReportAction;
use App\Actions\Reports\IndexArAgingReportAction;
use App\Http\Requests\Reports\ExportArAgingReportRequest;
use App\Http\Requests\Reports\IndexArAgingReportRequest;
use App\Http\Resources\Reports\ArAgingReportCollection;
use Illuminate\Http\JsonResponse;

class ArAgingReportController extends Controller
{
    public function index(
        IndexArAgingReportRequest $request,
        IndexArAgingReportAction $action
    ): ArAgingReportCollection {
        $rows = $action->execute($request);

        return new ArAgingReportCollection($rows);
    }

    public function export(
        ExportArAgingReportRequest $request,
        ExportArAgingReportAction $action
    ): JsonResponse {
        return $action->execute($request);
    }
}
