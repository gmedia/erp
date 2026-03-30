<?php

namespace App\Http\Controllers;

use App\Actions\InventoryStocktakes\SyncInventoryStocktakeItemsAction;
use App\Http\Controllers\Concerns\HandlesNestedItemsResponse;
use App\Http\Requests\InventoryStocktakes\UpdateInventoryStocktakeItemsRequest;
use App\Models\InventoryStocktake;
use Illuminate\Http\JsonResponse;

class InventoryStocktakeItemController extends Controller
{
    use HandlesNestedItemsResponse;

    public function getItems(InventoryStocktake $inventoryStocktake): JsonResponse
    {
        return $this->nestedItemsResponse(
            $inventoryStocktake,
            ['items.product', 'items.unit', 'items.countedBy'],
            fn ($item) => [
                'id' => $item->id,
                'product' => [
                    'id' => $item->product_id,
                    'name' => $item->product->name,
                ],
                'unit' => [
                    'id' => $item->unit_id,
                    'name' => $item->unit->name,
                ],
                'system_quantity' => (string) $item->system_quantity,
                'counted_quantity' => $item->counted_quantity === null ? null : (string) $item->counted_quantity,
                'variance' => $item->variance === null ? null : (string) $item->variance,
                'result' => $item->result,
                'notes' => $item->notes,
                'counted_by' => $item->countedBy ? [
                    'id' => $item->countedBy->id,
                    'name' => $item->countedBy->name,
                ] : null,
                'counted_at' => $item->counted_at?->toIso8601String(),
            ],
        );
    }

    public function syncItems(
        UpdateInventoryStocktakeItemsRequest $request,
        InventoryStocktake $inventoryStocktake,
        SyncInventoryStocktakeItemsAction $action,
    ): JsonResponse {
        $action->execute($inventoryStocktake, $request->validated()['items']);

        return $this->nestedItemsResponse(
            $inventoryStocktake,
            ['items.product', 'items.unit', 'items.countedBy'],
            fn ($item) => [
                'id' => $item->id,
                'product' => [
                    'id' => $item->product_id,
                    'name' => $item->product->name,
                ],
                'unit' => [
                    'id' => $item->unit_id,
                    'name' => $item->unit->name,
                ],
                'system_quantity' => (string) $item->system_quantity,
                'counted_quantity' => $item->counted_quantity === null ? null : (string) $item->counted_quantity,
                'variance' => $item->variance === null ? null : (string) $item->variance,
                'result' => $item->result,
                'notes' => $item->notes,
                'counted_by' => $item->countedBy ? [
                    'id' => $item->countedBy->id,
                    'name' => $item->countedBy->name,
                ] : null,
                'counted_at' => $item->counted_at?->toIso8601String(),
            ],
        );
    }
}
