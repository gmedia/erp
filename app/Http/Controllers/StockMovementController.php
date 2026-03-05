<?php

namespace App\Http\Controllers;

use App\Actions\StockMovements\ExportStockMovementsAction;
use App\Actions\StockMovements\IndexStockMovementsAction;
use App\Http\Requests\StockMovements\ExportStockMovementRequest;
use App\Http\Requests\StockMovements\IndexStockMovementRequest;
use App\Http\Resources\StockMovements\StockMovementCollection;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class StockMovementController extends Controller
{
    public function index(IndexStockMovementRequest $request, IndexStockMovementsAction $action): Response|StockMovementCollection
    {
        $movements = $action->execute($request);

        if ($request->wantsJson()) {
            return new StockMovementCollection($movements);
        }

        return Inertia::render('stock-movements/index', [
            'movements' => new StockMovementCollection($movements),
            'filters' => $request->only([
                'search',
                'product_id',
                'warehouse_id',
                'movement_type',
                'start_date',
                'end_date',
                'sort_by',
                'sort_direction',
                'per_page',
            ]),
        ]);
    }

    public function export(ExportStockMovementRequest $request, ExportStockMovementsAction $action): JsonResponse
    {
        return $action->execute($request);
    }
}

