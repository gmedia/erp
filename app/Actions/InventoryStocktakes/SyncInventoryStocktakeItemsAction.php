<?php

namespace App\Actions\InventoryStocktakes;

use App\Models\InventoryStocktake;
use App\Models\InventoryStocktakeItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SyncInventoryStocktakeItemsAction
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function execute(InventoryStocktake $inventoryStocktake, array $items): void
    {
        DB::transaction(function () use ($inventoryStocktake, $items) {
            $productIds = [];

            foreach ($items as $item) {
                $productIds[] = (int) $item['product_id'];

                $systemQuantity = (float) $item['system_quantity'];
                $countedQuantity = array_key_exists('counted_quantity', $item) ? $item['counted_quantity'] : null;
                $countedQuantity = $countedQuantity === null ? null : (float) $countedQuantity;

                $variance = null;
                $result = 'uncounted';
                $countedBy = null;
                $countedAt = null;

                if ($countedQuantity !== null) {
                    $variance = $countedQuantity - $systemQuantity;

                    if ($variance === 0.0) {
                        $result = 'match';
                    } elseif ($variance > 0.0) {
                        $result = 'surplus';
                    } else {
                        $result = 'deficit';
                    }

                    $countedBy = Auth::id();
                    $countedAt = now();
                }

                InventoryStocktakeItem::updateOrCreate(
                    [
                        'inventory_stocktake_id' => $inventoryStocktake->id,
                        'product_id' => $item['product_id'],
                    ],
                    [
                        'unit_id' => $item['unit_id'],
                        'system_quantity' => $systemQuantity,
                        'counted_quantity' => $countedQuantity,
                        'variance' => $variance,
                        'result' => $result,
                        'notes' => $item['notes'] ?? null,
                        'counted_by' => $countedBy,
                        'counted_at' => $countedAt,
                    ]
                );
            }

            $inventoryStocktake->items()
                ->when(! empty($productIds), fn ($q) => $q->whereNotIn('product_id', $productIds))
                ->delete();
        });
    }
}
