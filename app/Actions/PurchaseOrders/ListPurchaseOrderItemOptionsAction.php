<?php

namespace App\Actions\PurchaseOrders;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Collection;

class ListPurchaseOrderItemOptionsAction
{
    public function execute(PurchaseOrder $purchaseOrder): Collection
    {
        $options = [];

        $lines = PurchaseOrderItem::query()
            ->where('purchase_order_id', $purchaseOrder->id)
            ->with(['product:id,name', 'unit:id,name'])
            ->orderBy('id')
            ->get();

        foreach ($lines as $item) {
            $options[] = [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'unit_id' => $item->unit_id,
                'unit_name' => $item->unit->name,
                'unit_price' => (string) $item->unit_price,
                'quantity' => (string) $item->quantity,
                'quantity_received' => (string) $item->quantity_received,
                'remaining_quantity' => (string) max(0, (float) $item->quantity - (float) $item->quantity_received),
            ];
        }

        return collect($options);
    }
}
