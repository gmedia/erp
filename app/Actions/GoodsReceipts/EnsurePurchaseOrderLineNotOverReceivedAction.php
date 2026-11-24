<?php

namespace App\Actions\GoodsReceipts;

use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrderItem;
use Illuminate\Validation\ValidationException;

/**
 * Issue #436 keputusan product owner (1): jumlah GR confirmed per baris PO tidak boleh
 * melebihi qty baris PO. Tanpa toleransi. GR draft tidak dihitung.
 *
 * Dipanggil dalam transaksi create/update GR confirmed, setelah lock baris PO
 * (EnsurePurchaseOrderAllowsConfirmedReceiptAction), supaya dua GR bersamaan tidak lolos.
 */
class EnsurePurchaseOrderLineNotOverReceivedAction
{
    /**
     * Butir 2 dan 3: qty yang dihitung adalah quantity_accepted, dijumlahkan per baris PO
     * (beberapa baris GR untuk satu baris PO tidak boleh lolos satu per satu).
     *
     * @param  array<int, array<string, mixed>>  $items  item GR yang akan dikonfirmasi
     */
    public function execute(int $purchaseOrderId, array $items, ?int $excludeGoodsReceiptId = null): void
    {
        $incomingByLine = [];
        $indexByLine = [];

        foreach ($items as $index => $item) {
            $lineId = (int) ($item['purchase_order_item_id'] ?? 0);

            if ($lineId === 0) {
                continue;
            }

            $incomingByLine[$lineId] = ($incomingByLine[$lineId] ?? 0.0) + (float) ($item['quantity_accepted'] ?? 0);
            $indexByLine[$lineId] ??= $index;
        }

        foreach ($incomingByLine as $lineId => $incoming) {
            $line = PurchaseOrderItem::query()
                ->whereKey($lineId)
                ->where('purchase_order_id', $purchaseOrderId)
                ->lockForUpdate()
                ->first();

            if ($line === null) {
                continue;
            }

            $alreadyReceived = (float) GoodsReceiptItem::query()
                ->where('purchase_order_item_id', $lineId)
                ->whereHas('goodsReceipt', function ($query) use ($excludeGoodsReceiptId): void {
                    $query->where('status', 'confirmed');

                    if ($excludeGoodsReceiptId !== null) {
                        $query->whereKeyNot($excludeGoodsReceiptId);
                    }
                })
                ->sum('quantity_accepted');

            if ($alreadyReceived + $incoming > (float) $line->quantity + 0.00001) {
                throw ValidationException::withMessages([
                    'items.' . $indexByLine[$lineId] . '.quantity_received' => [
                        __('goods_receipt.received_exceeds_po_quantity'),
                    ],
                ])->status(422);
            }
        }
    }
}
