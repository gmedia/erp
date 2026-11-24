<?php

namespace App\Actions\GoodsReceipts;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\SupplierReturnItem;
use Illuminate\Support\Collection;

class ListGoodsReceiptItemOptionsAction
{
    public function execute(
        GoodsReceipt $goodsReceipt,
        ?string $search = null,
        ?int $currentLineId = null,
    ): Collection {
        $items = GoodsReceiptItem::query()
            ->where('goods_receipt_id', $goodsReceipt->id)
            ->when($search !== null && $search !== '', fn ($query) => $query->whereHas(
                'product',
                fn ($productQuery) => $productQuery->where('name', 'like', '%' . $search . '%'),
            ))
            ->with(['product:id,name', 'unit:id,name'])
            ->orderBy('id')
            ->get();

        $returnedByItem = SupplierReturnItem::query()
            ->selectRaw('goods_receipt_item_id, SUM(quantity_returned) AS returned')
            ->whereIn('goods_receipt_item_id', $items->pluck('id'))
            ->whereHas('supplierReturn', fn ($query) => $query->where('status', '!=', 'cancelled'))
            ->groupBy('goods_receipt_item_id')
            ->pluck('returned', 'goods_receipt_item_id');

        $options = [];

        foreach ($items as $item) {
            $remaining = max(0, (float) $item->quantity_accepted - (float) ($returnedByItem[$item->id] ?? 0));

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
                'quantity_accepted' => (string) $item->quantity_accepted,
                'remaining_quantity' => (string) $remaining,
            ];
        }

        return collect($options);
    }
}
