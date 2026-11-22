<?php

namespace App\Actions\StockAdjustments;

use App\Actions\Concerns\ResolvesStockBalance;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;

class PostStockAdjustmentMovementsAction
{
    use ResolvesStockBalance;

    /**
     * Catat pergerakan stok untuk adjustment yang sudah approved. Idempoten
     * terhadap reference (adjustment + produk + gudang + tipe) sehingga
     * approval ulang tidak menggandakan baris (W5-19).
     */
    public function execute(StockAdjustment $stockAdjustment): void
    {
        if ($stockAdjustment->status !== 'approved') {
            return;
        }

        // load() (bukan loadMissing) agar relasi tidak memakai cache pra-sync.
        $stockAdjustment->load('items');

        foreach ($stockAdjustment->items as $item) {
            $quantity = (float) $item->quantity_adjusted;

            if ($quantity === 0.0) {
                continue;
            }

            $movementType = $quantity > 0 ? 'adjustment_in' : 'adjustment_out';

            StockMovement::updateOrCreate(
                [
                    'movement_type' => $movementType,
                    'reference_type' => StockAdjustment::class,
                    'reference_id' => $stockAdjustment->id,
                    'warehouse_id' => $stockAdjustment->warehouse_id,
                    'product_id' => $item->product_id,
                ],
                [
                    'quantity_in' => $quantity > 0 ? abs($quantity) : 0,
                    'quantity_out' => $quantity < 0 ? abs($quantity) : 0,
                    'balance_after' => $this->currentStockBalance(
                        (int) $item->product_id,
                        (int) $stockAdjustment->warehouse_id,
                    ) + $quantity,
                    'unit_cost' => $item->unit_cost,
                    'reference_number' => $stockAdjustment->adjustment_number,
                    'notes' => $stockAdjustment->notes,
                    'moved_at' => $stockAdjustment->adjustment_date->startOfDay(),
                    'created_by' => $stockAdjustment->approved_by ?? Auth::id(),
                ],
            );
        }
    }
}
