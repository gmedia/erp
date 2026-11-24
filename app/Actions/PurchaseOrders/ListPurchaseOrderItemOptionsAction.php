<?php

namespace App\Actions\PurchaseOrders;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Collection;

class ListPurchaseOrderItemOptionsAction
{
    public function execute(
        PurchaseOrder $purchaseOrder,
        ?string $search = null,
        ?int $currentLineId = null,
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

        foreach ($lines as $item) {
            $remaining = max(0, (float) $item->quantity - (float) $item->quantity_received);

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
