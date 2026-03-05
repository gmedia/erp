<?php

namespace App\Actions\StockAdjustments;

use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use Illuminate\Support\Facades\DB;

class SyncStockAdjustmentItemsAction
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function execute(StockAdjustment $stockAdjustment, array $items): void
    {
        DB::transaction(function () use ($stockAdjustment, $items) {
            $productIds = [];

            foreach ($items as $item) {
                $productIds[] = (int) $item['product_id'];

                $quantityBefore = (float) ($item['quantity_before'] ?? 0);
                $quantityAdjusted = (float) $item['quantity_adjusted'];
                $quantityAfter = $quantityBefore + $quantityAdjusted;
                $unitCost = (float) ($item['unit_cost'] ?? 0);
                $totalCost = abs($quantityAdjusted) * $unitCost;

                StockAdjustmentItem::updateOrCreate(
                    [
                        'stock_adjustment_id' => $stockAdjustment->id,
                        'product_id' => $item['product_id'],
                    ],
                    [
                        'unit_id' => $item['unit_id'],
                        'quantity_before' => $quantityBefore,
                        'quantity_adjusted' => $quantityAdjusted,
                        'quantity_after' => $quantityAfter,
                        'unit_cost' => $unitCost,
                        'total_cost' => $totalCost,
                        'reason' => $item['reason'] ?? null,
                    ]
                );
            }

            $stockAdjustment->items()
                ->when(!empty($productIds), fn ($q) => $q->whereNotIn('product_id', $productIds))
                ->delete();
        });
    }
}
