<?php

namespace App\Actions\PurchaseOrders;

use App\Models\PurchaseOrder;
use Illuminate\Support\Collection;

class SyncPurchaseOrderItemsAction
{
    public function execute(PurchaseOrder $purchaseOrder, array $items): void
    {
        $normalized = Collection::make($items)->map(static function (array $item): array {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $discountPercent = (float) ($item['discount_percent'] ?? 0);
            $taxPercent = (float) ($item['tax_percent'] ?? 0);

            $lineBeforeTax = $quantity * $unitPrice * (1 - ($discountPercent / 100));
            $lineTotal = $lineBeforeTax * (1 + ($taxPercent / 100));

            return [
                'purchase_request_item_id' => $item['purchase_request_item_id'] ?? null,
                'product_id' => (int) $item['product_id'],
                'unit_id' => (int) $item['unit_id'],
                'quantity' => $quantity,
                'quantity_received' => 0,
                'unit_price' => $unitPrice,
                'discount_percent' => $discountPercent,
                'tax_percent' => $taxPercent,
                'line_total' => $lineTotal,
                'notes' => $item['notes'] ?? null,
            ];
        })->values()->all();

        $purchaseOrder->items()->delete();
        $purchaseOrder->items()->createMany($normalized);

        $subtotal = collect($normalized)
            ->sum(static fn (array $row) => (float) ($row['quantity'] * $row['unit_price']));
        $discountAmount = collect($normalized)
            ->sum(static function (array $row): float {
                $lineSubtotal = (float) ($row['quantity'] * $row['unit_price']);

                return (float) ($lineSubtotal * ($row['discount_percent'] / 100));
            });
        $taxAmount = collect($normalized)
            ->sum(static function (array $row): float {
                $lineSubtotal = (float) ($row['quantity'] * $row['unit_price']);
                $discountedSubtotal = $lineSubtotal * (1 - ($row['discount_percent'] / 100));

                return (float) ($discountedSubtotal * ($row['tax_percent'] / 100));
            });
        $grandTotal = collect($normalized)->sum(static fn (array $row) => (float) ($row['line_total']));

        $purchaseOrder->update([
            'subtotal' => (string) $subtotal,
            'discount_amount' => (string) $discountAmount,
            'tax_amount' => (string) $taxAmount,
            'grand_total' => (string) $grandTotal,
        ]);
    }
}
