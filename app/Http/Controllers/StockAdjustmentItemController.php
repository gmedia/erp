<?php

namespace App\Http\Controllers;

use App\Actions\StockAdjustments\SyncStockAdjustmentItemsAction;
use App\Http\Requests\StockAdjustments\UpdateStockAdjustmentItemsRequest;
use App\Models\StockAdjustment;
use Illuminate\Http\JsonResponse;

class StockAdjustmentItemController extends Controller
{
    public function getItems(StockAdjustment $stockAdjustment): JsonResponse
    {
        $stockAdjustment->load(['items.product', 'items.unit']);

        return response()->json([
            'data' => $stockAdjustment->items->map(fn ($item) => [
                'id' => $item->id,
                'product' => [
                    'id' => $item->product_id,
                    'name' => $item->product->name,
                ],
                'unit' => [
                    'id' => $item->unit_id,
                    'name' => $item->unit->name,
                ],
                'quantity_before' => (string) $item->quantity_before,
                'quantity_adjusted' => (string) $item->quantity_adjusted,
                'quantity_after' => (string) $item->quantity_after,
                'unit_cost' => (string) $item->unit_cost,
                'total_cost' => (string) $item->total_cost,
                'reason' => $item->reason,
            ])->values(),
        ]);
    }

    public function syncItems(
        UpdateStockAdjustmentItemsRequest $request,
        StockAdjustment $stockAdjustment,
        SyncStockAdjustmentItemsAction $action,
    ): JsonResponse
    {
        $action->execute($stockAdjustment, $request->validated()['items']);

        $stockAdjustment->load(['items.product', 'items.unit']);

        return response()->json([
            'data' => $stockAdjustment->items->map(fn ($item) => [
                'id' => $item->id,
                'product' => [
                    'id' => $item->product_id,
                    'name' => $item->product->name,
                ],
                'unit' => [
                    'id' => $item->unit_id,
                    'name' => $item->unit->name,
                ],
                'quantity_before' => (string) $item->quantity_before,
                'quantity_adjusted' => (string) $item->quantity_adjusted,
                'quantity_after' => (string) $item->quantity_after,
                'unit_cost' => (string) $item->unit_cost,
                'total_cost' => (string) $item->total_cost,
                'reason' => $item->reason,
            ])->values(),
        ]);
    }
}
