<?php

namespace App\Http\Controllers;

use App\Actions\StockMonitor\ExportStockMonitorAction;
use App\Actions\StockMonitor\IndexStockMonitorAction;
use App\Http\Controllers\Concerns\ResolvesBranchScope;
use App\Http\Requests\StockMonitor\ExportStockMonitorRequest;
use App\Http\Requests\StockMonitor\IndexStockMonitorRequest;
use App\Http\Resources\StockMonitor\StockMonitorCollection;
use Illuminate\Http\JsonResponse;

class StockMonitorController extends Controller
{
    use ResolvesBranchScope;

    public function index(IndexStockMonitorRequest $request, IndexStockMonitorAction $action): StockMonitorCollection
    {
        $this->forceBranchScope($request);

        $result = $action->execute($request);
        $stocks = $result['stocks'];
        $summary = $result['summary'];

        return new StockMonitorCollection($stocks, $summary);
    }

    public function export(ExportStockMonitorRequest $request, ExportStockMonitorAction $action): JsonResponse
    {
        $this->forceBranchScope($request);

        return $action->execute($request);
    }

    private function forceBranchScope(IndexStockMonitorRequest|ExportStockMonitorRequest $request): void
    {
        $effective = $this->resolveBranchFromRequest($request);

        if ($effective === null) {
            $request->offsetUnset('branch_id');

            return;
        }

        $request->merge(['branch_id' => $effective]);
    }
}
