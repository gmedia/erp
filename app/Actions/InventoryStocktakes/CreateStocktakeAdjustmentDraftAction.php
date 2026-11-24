<?php

namespace App\Actions\InventoryStocktakes;

use App\Actions\StockAdjustments\SyncStockAdjustmentItemsAction;
use App\Models\InventoryStocktake;
use App\Models\InventoryStocktakeItem;
use App\Models\StockAdjustment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Issue #436 keputusan (3): saat stocktake selesai, buat tepat satu draft penyesuaian stok
 * yang hanya memuat baris berselisih. Stok tidak berubah sampai draft disetujui.
 */
class CreateStocktakeAdjustmentDraftAction
{
    public function __construct(
        private readonly SyncStockAdjustmentItemsAction $syncItems,
    ) {}

    public function execute(InventoryStocktake $stocktake): ?StockAdjustment
    {
        $exists = StockAdjustment::query()
            ->where('inventory_stocktake_id', $stocktake->id)
            ->exists();

        if ($exists) {
            return null;
        }

        /** @var Collection<int, InventoryStocktakeItem> $lines */
        $lines = InventoryStocktakeItem::query()
            ->where('inventory_stocktake_id', $stocktake->id)
            ->whereNotNull('variance')
            ->where('variance', '!=', 0)
            ->get();

        if ($lines->isEmpty()) {
            return null;
        }

        $adjustment = StockAdjustment::create([
            'warehouse_id' => $stocktake->warehouse_id,
            'adjustment_date' => now()->toDateString(),
            'adjustment_type' => 'stocktake_result',
            'status' => 'draft',
            'inventory_stocktake_id' => $stocktake->id,
            'notes' => 'Draft dari stocktake ' . $stocktake->stocktake_number,
            'created_by' => Auth::id(),
        ]);

        $this->syncItems->execute($adjustment, $lines->map(static fn (InventoryStocktakeItem $line): array => [
            'product_id' => $line->product_id,
            'unit_id' => $line->unit_id,
            'quantity_before' => (float) $line->system_quantity,
            'quantity_adjusted' => (float) $line->variance,
            'unit_cost' => 0,
            'reason' => 'stocktake_result',
        ])->all());

        return $adjustment;
    }
}
