<?php

namespace App\Actions\GoodsReceipts;

use App\Actions\Concerns\ResolvesStockBalance;
use App\Models\GoodsReceipt;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;

class PostGoodsReceiptMovementsAction
{
    use ResolvesStockBalance;

    /**
     * Barang diterima menambah stok gudang tujuan. Idempoten terhadap
     * reference (GR + produk + gudang) sehingga konfirmasi ulang aman (W5-19).
     */
    public function execute(GoodsReceipt $goodsReceipt): void
    {
        if ($goodsReceipt->status !== 'confirmed') {
            return;
        }

        // load() (bukan loadMissing) agar relasi tidak memakai cache pra-sync.
        $goodsReceipt->load('items');

        foreach ($goodsReceipt->items as $item) {
            $quantity = (float) $item->quantity_accepted;

            if ($quantity <= 0) {
                continue;
            }

            StockMovement::updateOrCreate(
                [
                    'movement_type' => 'goods_receipt',
                    'reference_type' => GoodsReceipt::class,
                    'reference_id' => $goodsReceipt->id,
                    'warehouse_id' => $goodsReceipt->warehouse_id,
                    'product_id' => $item->product_id,
                ],
                [
                    'quantity_in' => $quantity,
                    'quantity_out' => 0,
                    'balance_after' => $this->currentStockBalance(
                        (int) $item->product_id,
                        (int) $goodsReceipt->warehouse_id,
                    ) + $quantity,
                    'unit_cost' => $item->unit_price,
                    'reference_number' => $goodsReceipt->gr_number,
                    'notes' => $goodsReceipt->notes,
                    'moved_at' => $goodsReceipt->receipt_date->startOfDay(),
                    'created_by' => $goodsReceipt->confirmed_by ?? Auth::id(),
                ],
            );
        }
    }
}
