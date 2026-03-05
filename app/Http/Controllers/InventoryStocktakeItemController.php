<?php

namespace App\Http\Controllers;

use App\Actions\InventoryStocktakes\SyncInventoryStocktakeItemsAction;
use App\Http\Requests\InventoryStocktakes\UpdateInventoryStocktakeItemsRequest;
use App\Models\InventoryStocktake;
use Illuminate\Http\JsonResponse;

class InventoryStocktakeItemController extends Controller
{
    public function getItems(InventoryStocktake $inventoryStocktake): JsonResponse
    {
        $inventoryStocktake->load(['items.product', 'items.unit', 'items.countedBy']);

        return response()->json([
            'data' => $inventoryStocktake->items->map(fn ($item) => [
                'id' => $item->id,
                'product' => [
                    'id' => $item->product_id,
                    'name' => $item->product?->name,
                ],
                'unit' => [
                    'id' => $item->unit_id,
                    'name' => $item->unit?->name,
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
            ])->values(),
        ]);
    }

    public function syncItems(UpdateInventoryStocktakeItemsRequest $request, InventoryStocktake $inventoryStocktake, SyncInventoryStocktakeItemsAction $action): JsonResponse
    {
        $action->execute($inventoryStocktake, $request->validated()['items']);

        $inventoryStocktake->load(['items.product', 'items.unit', 'items.countedBy']);

        return response()->json([
            'data' => $inventoryStocktake->items->map(fn ($item) => [
                'id' => $item->id,
                'product' => [
                    'id' => $item->product_id,
                    'name' => $item->product?->name,
                ],
                'unit' => [
                    'id' => $item->unit_id,
                    'name' => $item->unit?->name,
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
            ])->values(),
        ]);
    }
}

