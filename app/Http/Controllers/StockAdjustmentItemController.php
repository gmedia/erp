<?php

namespace App\Http\Controllers;

use App\Actions\StockAdjustments\SyncStockAdjustmentItemsAction;
use App\Http\Controllers\Concerns\HandlesNestedItemsResponse;
use App\Http\Requests\StockAdjustments\UpdateStockAdjustmentItemsRequest;
use App\Models\StockAdjustment;
use Illuminate\Http\JsonResponse;

class StockAdjustmentItemController extends Controller
{
    use HandlesNestedItemsResponse;

    public function getItems(StockAdjustment $stockAdjustment): JsonResponse
    {
        return $this->nestedItemsResponse($stockAdjustment, ['items.product', 'items.unit'], fn ($item) => [
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
            ]);
    }

    public function syncItems(
        UpdateStockAdjustmentItemsRequest $request,
        StockAdjustment $stockAdjustment,
        SyncStockAdjustmentItemsAction $action,
    ): JsonResponse {
        $action->execute($stockAdjustment, $request->validated()['items']);

        return $this->nestedItemsResponse($stockAdjustment, ['items.product', 'items.unit'], fn ($item) => [
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
            ]);
    }
}
