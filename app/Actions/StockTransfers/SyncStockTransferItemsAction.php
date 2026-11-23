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
                    // Issue #356 baris 5: tanpa quantity_received, transfer diterima penuh.
                    // Nilai eksplisit (termasuk 0) tetap dihormati.
                    'quantity_received' => array_key_exists('quantity_received', $item)
                        ? $item['quantity_received']
                        : $item['quantity'],
                    'unit_cost' => $item['unit_cost'] ?? 0,
                    'notes' => $item['notes'] ?? null,
                ],
            );
        });
    }
}
