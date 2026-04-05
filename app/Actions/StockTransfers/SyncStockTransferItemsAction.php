<?php

namespace App\Actions\StockTransfers;

use App\Actions\Concerns\UpsertsItemsWithCleanup;
use App\Models\StockTransfer;
use Illuminate\Support\Facades\DB;

class SyncStockTransferItemsAction
{
    use UpsertsItemsWithCleanup;

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function execute(StockTransfer $stockTransfer, array $items): void
    {
        DB::transaction(function () use ($stockTransfer, $items) {
            $this->upsertItemsWithCleanup(
                $stockTransfer->items(),
                $items,
                'product_id',
                static fn (array $item): array => [
                    'unit_id' => $item['unit_id'],
                    'quantity' => $item['quantity'],
                    'quantity_received' => $item['quantity_received'] ?? 0,
                    'unit_cost' => $item['unit_cost'] ?? 0,
                    'notes' => $item['notes'] ?? null,
                ],
            );
        });
    }
}
