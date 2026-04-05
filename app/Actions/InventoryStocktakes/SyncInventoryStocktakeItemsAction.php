<?php

namespace App\Actions\InventoryStocktakes;

use App\Actions\Concerns\UpsertsItemsWithCleanup;
use App\Models\InventoryStocktake;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SyncInventoryStocktakeItemsAction
{
    use UpsertsItemsWithCleanup;

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function execute(InventoryStocktake $inventoryStocktake, array $items): void
    {
        DB::transaction(function () use ($inventoryStocktake, $items) {
            $this->upsertItemsWithCleanup(
                $inventoryStocktake->items(),
                $items,
                'product_id',
                static function (array $item): array {
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

                    return [
                        'unit_id' => $item['unit_id'],
                        'system_quantity' => $systemQuantity,
                        'counted_quantity' => $countedQuantity,
                        'variance' => $variance,
                        'result' => $result,
                        'notes' => $item['notes'] ?? null,
                        'counted_by' => $countedBy,
                        'counted_at' => $countedAt,
                    ];
                },
            );
        });
    }
}
