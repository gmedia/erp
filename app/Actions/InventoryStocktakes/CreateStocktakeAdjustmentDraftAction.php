<?php

namespace App\Actions\InventoryStocktakes;

use App\Actions\StockAdjustments\SyncStockAdjustmentItemsAction;
use App\Models\InventoryStocktake;
use App\Models\InventoryStocktakeItem;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
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
            'notes' => __('inventory_stocktake.adjustment_note', ['stocktake' => $stocktake->stocktake_number]),
            'created_by' => Auth::id(),
        ]);

        $this->assignDocumentNumber($adjustment);

        $this->syncItems->execute($adjustment, $lines->map(fn (InventoryStocktakeItem $line): array => [
            'product_id' => $line->product_id,
            'unit_id' => $line->unit_id,
            'quantity_before' => (float) $line->system_quantity,
            'quantity_adjusted' => (float) $line->variance,
            'unit_cost' => $this->unitCost((int) $line->product_id, (int) $stocktake->warehouse_id),
            'reason' => 'stocktake_result',
        ])->all());

        return $adjustment;
    }

    /**
     * Nomor dokumen memakai urutan yang sama dengan jalur penyesuaian biasa (SA-YYYY-NNNNNN).
     */
    private function assignDocumentNumber(StockAdjustment $adjustment): void
    {
        $yearPrefix = 'SA-' . now()->format('Y') . '-';

        $lastSequence = StockAdjustment::query()
            ->where('adjustment_number', 'like', $yearPrefix . '%')
            ->pluck('adjustment_number')
            ->map(fn ($value): int => (int) substr((string) $value, strlen($yearPrefix)))
            ->max() ?? 0;

        $adjustment->update([
            'adjustment_number' => $yearPrefix . str_pad((string) ($lastSequence + 1), 6, '0', STR_PAD_LEFT),
        ]);
    }

    /**
     * Biaya rata-rata produk di gudang: average_cost_after movement terakhir, bila belum ada
     * movement memakai biaya master produk (Issue #436 butir 6; bukan nol).
     */
    private function unitCost(int $productId, int $warehouseId): float
    {
        $average = StockMovement::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->whereNotNull('average_cost_after')
            ->orderByDesc('moved_at')
            ->orderByDesc('id')
            ->value('average_cost_after');

        if ($average !== null) {
            return (float) $average;
        }

        return (float) Product::query()->whereKey($productId)->value('cost');
    }
}
