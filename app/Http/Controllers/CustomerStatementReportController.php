<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ExportCustomerStatementReportAction;
use App\Actions\Reports\IndexCustomerStatementReportAction;
use App\Http\Requests\Reports\ExportCustomerStatementReportRequest;
use App\Http\Requests\Reports\IndexCustomerStatementReportRequest;
use App\Http\Resources\Reports\CustomerStatementReportCollection;
use Illuminate\Http\JsonResponse;

class CustomerStatementReportController extends Controller
{
    public function index(
        IndexCustomerStatementReportRequest $request,
        IndexCustomerStatementReportAction $action
    ): CustomerStatementReportCollection {
        $rows = $action->execute($request);

        return new CustomerStatementReportCollection($rows);
    }

    public function export(
        ExportCustomerStatementReportRequest $request,
        ExportCustomerStatementReportAction $action
    ): JsonResponse {
        return $action->execute($request);
    }
}
