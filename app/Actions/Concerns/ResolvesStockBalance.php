<?php

namespace App\Actions\Concerns;

use App\Models\StockMovement;

trait ResolvesStockBalance
{
    /**
     * Saldo stok terakhir untuk produk + gudang. Dipakai saat menulis movement
     * baru agar `balance_after` berurutan (W5-19).
     */
    protected function currentStockBalance(int $productId, int $warehouseId): float
    {
        return (float) (StockMovement::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->orderByDesc('moved_at')
            ->orderByDesc('id')
            ->value('balance_after') ?? 0);
    }
}
