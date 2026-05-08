<?php

namespace App\Actions\SupplierBills;

use App\Actions\Concerns\CalculatesTransactionLineTotals;
use App\Actions\Concerns\RecreatesItems;
use App\Models\SupplierBill;

class SyncSupplierBillItemsAction
{
    use CalculatesTransactionLineTotals;
    use RecreatesItems;

    public function execute(SupplierBill $supplierBill, array $items): void
    {
        $normalized = $this->recreateItems($supplierBill->items(), $items, function (array $item): array {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $discountPercent = (float) ($item['discount_percent'] ?? 0);
            $taxPercent = (float) ($item['tax_percent'] ?? 0);

            return [
                'product_id' => isset($item['product_id']) ? (int) $item['product_id'] : null,
                'account_id' => (int) $item['account_id'],
                'description' => $item['description'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_percent' => $discountPercent,
                'tax_percent' => $taxPercent,
                'line_total' => $this->calculateLineTotal($quantity, $unitPrice, $discountPercent, $taxPercent),
                'goods_receipt_item_id' => $item['goods_receipt_item_id'] ?? null,
                'notes' => $item['notes'] ?? null,
            ];
        });

        $totals = $this->calculateHeaderTotals($normalized);

        $supplierBill->update(array_merge($totals, [
            'amount_due' => (string) ((float) $totals['grand_total'] - (float) $supplierBill->amount_paid),
        ]));
    }
}
