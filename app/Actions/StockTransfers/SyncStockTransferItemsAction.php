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
        $isReceived = $stockTransfer->status === 'received';

        DB::transaction(function () use ($stockTransfer, $items, $isReceived) {
            $this->upsertItemsWithCleanup(
                $stockTransfer->items(),
                $items,
                'product_id',
                static fn (array $item): array => [
                    'unit_id' => $item['unit_id'],
                    'quantity' => $item['quantity'],
                    // Issue #360 review baris 2: qty diterima hanya bermakna saat received.
                    // Sebelum itu selalu null ("belum diisi"), agar tidak mengunci angka palsu.
                    'quantity_received' => $isReceived ? ($item['quantity_received'] ?? $item['quantity']) : null,
                    'unit_cost' => $item['unit_cost'] ?? 0,
                    'notes' => $item['notes'] ?? null,
                ],
            );
        });
    }
}
