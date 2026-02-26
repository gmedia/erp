<?php

namespace App\Actions\StockTransfers;

use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use Illuminate\Support\Facades\DB;

class SyncStockTransferItemsAction
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function execute(StockTransfer $stockTransfer, array $items): void
    {
        DB::transaction(function () use ($stockTransfer, $items) {
            $productIds = [];

            foreach ($items as $item) {
                $productIds[] = (int) $item['product_id'];

                StockTransferItem::updateOrCreate(
                    [
                        'stock_transfer_id' => $stockTransfer->id,
                        'product_id' => $item['product_id'],
                    ],
                    [
                        'unit_id' => $item['unit_id'],
                        'quantity' => $item['quantity'],
                        'quantity_received' => $item['quantity_received'] ?? 0,
                        'unit_cost' => $item['unit_cost'] ?? 0,
                        'notes' => $item['notes'] ?? null,
                    ]
                );
            }

            $stockTransfer->items()
                ->when(!empty($productIds), fn ($q) => $q->whereNotIn('product_id', $productIds))
                ->delete();
        });
    }
}
