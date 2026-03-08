<?php

namespace App\Http\Controllers;

use App\Actions\StockMovements\ExportStockMovementsAction;
use App\Actions\StockMovements\IndexStockMovementsAction;
use App\Http\Requests\StockMovements\ExportStockMovementRequest;
use App\Http\Requests\StockMovements\IndexStockMovementRequest;
use App\Http\Resources\StockMovements\StockMovementCollection;
use Illuminate\Http\JsonResponse;

class StockMovementController extends Controller
{
    public function index(IndexStockMovementRequest $request, IndexStockMovementsAction $action): StockMovementCollection
    {
        $movements = $action->execute($request);

        return new StockMovementCollection($movements);
    }

    public function export(ExportStockMovementRequest $request, ExportStockMovementsAction $action): JsonResponse
    {
        return $action->execute($request);
    }
}

