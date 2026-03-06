<?php

namespace App\Actions\GoodsReceipts;

use App\Models\GoodsReceipt;
use Illuminate\Support\Collection;

class SyncGoodsReceiptItemsAction
{
    public function execute(GoodsReceipt $goodsReceipt, array $items): void
    {
        $normalized = Collection::make($items)->map(static function (array $item): array {
            $quantityReceived = (float) $item['quantity_received'];
            $quantityRejected = (float) ($item['quantity_rejected'] ?? 0);
            $quantityAccepted = array_key_exists('quantity_accepted', $item)
                ? (float) $item['quantity_accepted']
                : max(0, $quantityReceived - $quantityRejected);

            return [
                'purchase_order_item_id' => (int) $item['purchase_order_item_id'],
                'product_id' => (int) $item['product_id'],
                'unit_id' => (int) $item['unit_id'],
                'quantity_received' => $quantityReceived,
                'quantity_accepted' => $quantityAccepted,
                'quantity_rejected' => $quantityRejected,
                'unit_price' => (float) $item['unit_price'],
                'notes' => $item['notes'] ?? null,
            ];
        })->values()->all();

        $goodsReceipt->items()->delete();
        $goodsReceipt->items()->createMany($normalized);
    }
}
