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
     * @param  array<int, array<string, mixed>>  $items
     */
    public function execute(int $purchaseOrderId, array $items, ?int $excludeGoodsReceiptId = null): void
    {
        foreach ($items as $index => $item) {
            $lineId = (int) ($item['purchase_order_item_id'] ?? 0);

            if ($lineId === 0) {
                continue;
            }

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
                ->sum('quantity_received');

            $incoming = (float) ($item['quantity_received'] ?? 0);

            if ($alreadyReceived + $incoming > (float) $line->quantity + 0.00001) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity_received" => [__('goods_receipt.received_exceeds_po_quantity')],
                ]);
            }
        }
    }
}
