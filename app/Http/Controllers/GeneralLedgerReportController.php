<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ExportGeneralLedgerReportAction;
use App\Actions\Reports\GetGeneralLedgerReportAction;
use App\Http\Requests\Reports\GeneralLedgerReportRequest;
use Illuminate\Http\JsonResponse;

class GeneralLedgerReportController extends Controller
{
    public function index(GeneralLedgerReportRequest $request, GetGeneralLedgerReportAction $action): JsonResponse
    {
        return response()->json(['data' => $action->execute($request->validated())]);
    }

    public function export(GeneralLedgerReportRequest $request, ExportGeneralLedgerReportAction $action): JsonResponse
    {
        return $action->execute($request->validated());
    }
}
