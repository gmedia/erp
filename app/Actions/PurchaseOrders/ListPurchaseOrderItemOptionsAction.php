<?php

namespace App\Actions\PurchaseOrders;

use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Collection;

class ListPurchaseOrderItemOptionsAction
{
    public function execute(
        PurchaseOrder $purchaseOrder,
        ?string $search = null,
        ?int $currentLineId = null,
        ?int $editingGoodsReceiptId = null,
    ): Collection {
        $options = [];

        $lines = PurchaseOrderItem::query()
            ->where('purchase_order_id', $purchaseOrder->id)
            ->when($search !== null && $search !== '', fn ($query) => $query->whereHas(
                'product',
                fn ($productQuery) => $productQuery->where('name', 'like', '%' . $search . '%'),
            ))
            ->with(['product:id,name', 'unit:id,name'])
            ->orderBy('id')
            ->get();

        // Qty GR yang sedang diedit sudah ikut tercatat di quantity_received PO (confirmed),
        // jadi tidak boleh dihitung sebagai pemakaian sisa saat dokumen itu sendiri diedit.
        $ownReceivedByLine = $editingGoodsReceiptId === null
            ? []
            : GoodsReceiptItem::query()
                ->selectRaw('purchase_order_item_id, SUM(quantity_accepted) AS received')
                ->where('goods_receipt_id', $editingGoodsReceiptId)
                ->whereHas('goodsReceipt', fn ($query) => $query->where('status', 'confirmed'))
                ->groupBy('purchase_order_item_id')
                ->pluck('received', 'purchase_order_item_id')
                ->all();

        foreach ($lines as $item) {
            $ownReceived = (float) ($ownReceivedByLine[$item->id] ?? 0);
            $remaining = max(0, (float) $item->quantity - (float) $item->quantity_received + $ownReceived);

            // Baris bersisa 0 hanya ditawarkan untuk baris dokumen yang sedang diedit.
            if ($remaining <= 0 && $item->id !== $currentLineId) {
                continue;
            }

            $options[] = [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'unit_id' => $item->unit_id,
                'unit_name' => $item->unit->name,
                'unit_price' => (string) $item->unit_price,
                'quantity' => (string) $item->quantity,
                'quantity_received' => (string) $item->quantity_received,
                'remaining_quantity' => (string) $remaining,
            ];
        }

        return collect($options);
    }
}
