<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ExportInventoryStocktakeVarianceReportAction;
use App\Actions\Reports\IndexInventoryStocktakeVarianceReportAction;
use App\Http\Requests\Reports\ExportInventoryStocktakeVarianceReportRequest;
use App\Http\Requests\Reports\IndexInventoryStocktakeVarianceReportRequest;
use App\Http\Resources\Reports\InventoryStocktakeVarianceReportCollection;
use Illuminate\Http\JsonResponse;

class InventoryStocktakeVarianceReportController extends Controller
{
    public function index(
        IndexInventoryStocktakeVarianceReportRequest $request,
        IndexInventoryStocktakeVarianceReportAction $action
    ): InventoryStocktakeVarianceReportCollection {
        $rows = $action->execute($request);

        return new InventoryStocktakeVarianceReportCollection($rows);
    }

    public function export(
        ExportInventoryStocktakeVarianceReportRequest $request,
        ExportInventoryStocktakeVarianceReportAction $action
    ): JsonResponse {
        return $action->execute($request);
    }
}
