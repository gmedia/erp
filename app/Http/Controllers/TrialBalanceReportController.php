<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ExportTrialBalanceReportAction;
use App\Actions\Reports\GetTrialBalanceReportAction;
use App\Http\Requests\Reports\TrialBalanceReportRequest;
use Illuminate\Http\JsonResponse;

class TrialBalanceReportController extends Controller
{
    public function index(TrialBalanceReportRequest $request, GetTrialBalanceReportAction $action): JsonResponse
    {
        return response()->json($action->execute($request->validated()));
    }

    public function export(TrialBalanceReportRequest $request, ExportTrialBalanceReportAction $action): JsonResponse
    {
        return $action->execute($request->validated());
    }
}
