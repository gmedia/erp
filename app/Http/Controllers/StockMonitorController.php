<?php

namespace App\Http\Controllers;

use App\Actions\StockMonitor\ExportStockMonitorAction;
use App\Actions\StockMonitor\IndexStockMonitorAction;
use App\Http\Requests\StockMonitor\ExportStockMonitorRequest;
use App\Http\Requests\StockMonitor\IndexStockMonitorRequest;
use App\Http\Resources\StockMonitor\StockMonitorCollection;
use Illuminate\Http\JsonResponse;

class StockMonitorController extends Controller
{
    public function index(IndexStockMonitorRequest $request, IndexStockMonitorAction $action): StockMonitorCollection
    {
        $result = $action->execute($request);
        $stocks = $result['stocks'];
        $summary = $result['summary'];

        return new StockMonitorCollection($stocks, $summary);
    }

    public function export(ExportStockMonitorRequest $request, ExportStockMonitorAction $action): JsonResponse
    {
        return $action->execute($request);
    }
}
