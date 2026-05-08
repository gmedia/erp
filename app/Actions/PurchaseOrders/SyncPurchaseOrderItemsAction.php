<?php

namespace App\Actions\PurchaseOrders;

use App\Actions\Concerns\CalculatesTransactionLineTotals;
use App\Actions\Concerns\RecreatesItems;
use App\Models\PurchaseOrder;

class SyncPurchaseOrderItemsAction
{
    use CalculatesTransactionLineTotals;
    use RecreatesItems;

    public function execute(PurchaseOrder $purchaseOrder, array $items): void
    {
        $normalized = $this->recreateItems($purchaseOrder->items(), $items, function (array $item): array {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $discountPercent = (float) ($item['discount_percent'] ?? 0);
            $taxPercent = (float) ($item['tax_percent'] ?? 0);

            return [
                'purchase_request_item_id' => $item['purchase_request_item_id'] ?? null,
                'product_id' => (int) $item['product_id'],
                'unit_id' => (int) $item['unit_id'],
                'quantity' => $quantity,
                'quantity_received' => 0,
                'unit_price' => $unitPrice,
                'discount_percent' => $discountPercent,
                'tax_percent' => $taxPercent,
                'line_total' => $this->calculateLineTotal($quantity, $unitPrice, $discountPercent, $taxPercent),
                'notes' => $item['notes'] ?? null,
            ];
        });

        $purchaseOrder->update($this->calculateHeaderTotals($normalized));
    }
}
