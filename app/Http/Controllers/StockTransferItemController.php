<?php

namespace App\Http\Controllers;

use App\Actions\StockTransfers\SyncStockTransferItemsAction;
use App\Http\Controllers\Concerns\HandlesNestedItemsResponse;
use App\Http\Requests\StockTransfers\UpdateStockTransferItemsRequest;
use App\Models\StockTransfer;
use Illuminate\Http\JsonResponse;

class StockTransferItemController extends Controller
{
    use HandlesNestedItemsResponse;

    public function getItems(StockTransfer $stockTransfer): JsonResponse
    {
        return $this->nestedItemsResponse($stockTransfer, ['items.product', 'items.unit'], fn ($item) => [
                'id' => $item->id,
                'product' => [
                    'id' => $item->product_id,
                    'name' => $item->product->name,
                ],
                'unit' => [
                    'id' => $item->unit_id,
                    'name' => $item->unit->name,
                ],
                'quantity' => (string) $item->quantity,
                'quantity_received' => (string) $item->quantity_received,
                'unit_cost' => (string) $item->unit_cost,
                'notes' => $item->notes,
            ]);
    }

    public function syncItems(
        UpdateStockTransferItemsRequest $request,
        StockTransfer $stockTransfer,
        SyncStockTransferItemsAction $action,
    ): JsonResponse {
        $action->execute($stockTransfer, $request->validated()['items']);

        return $this->nestedItemsResponse($stockTransfer, ['items.product', 'items.unit'], fn ($item) => [
                'id' => $item->id,
                'product' => [
                    'id' => $item->product_id,
                    'name' => $item->product->name,
                ],
                'unit' => [
                    'id' => $item->unit_id,
                    'name' => $item->unit->name,
                ],
                'quantity' => (string) $item->quantity,
                'quantity_received' => (string) $item->quantity_received,
                'unit_cost' => (string) $item->unit_cost,
                'notes' => $item->notes,
            ]);
    }
}
