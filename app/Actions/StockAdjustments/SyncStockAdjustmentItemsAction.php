<?php

namespace App\Actions\StockAdjustments;

use App\Actions\Concerns\UpsertsItemsWithCleanup;
use App\Models\StockAdjustment;
use Illuminate\Support\Facades\DB;

class SyncStockAdjustmentItemsAction
{
    use UpsertsItemsWithCleanup;

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function execute(StockAdjustment $stockAdjustment, array $items): void
    {
        DB::transaction(function () use ($stockAdjustment, $items) {
            $this->upsertItemsWithCleanup(
                $stockAdjustment->items(),
                $items,
                'product_id',
                static function (array $item): array {
                    $quantityBefore = (float) ($item['quantity_before'] ?? 0);
                    $quantityAdjusted = (float) $item['quantity_adjusted'];
                    $quantityAfter = $quantityBefore + $quantityAdjusted;
                    $unitCost = (float) ($item['unit_cost'] ?? 0);
                    $totalCost = abs($quantityAdjusted) * $unitCost;

                    return [
                        'unit_id' => $item['unit_id'],
                        'quantity_before' => $quantityBefore,
                        'quantity_adjusted' => $quantityAdjusted,
                        'quantity_after' => $quantityAfter,
                        'unit_cost' => $unitCost,
                        'total_cost' => $totalCost,
                        'reason' => $item['reason'] ?? null,
                    ];
                },
            );
        });
    }
}
