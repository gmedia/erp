<?php

namespace App\Actions\CustomerInvoices;

use App\Actions\Concerns\CalculatesTransactionLineTotals;
use App\Actions\Concerns\RecreatesItems;
use App\Models\CustomerInvoice;

class SyncCustomerInvoiceItemsAction
{
    use CalculatesTransactionLineTotals;
    use RecreatesItems;

    public function execute(CustomerInvoice $customerInvoice, array $items): void
    {
        $normalized = $this->recreateItems($customerInvoice->items(), $items, function (array $item): array {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $discountPercent = (float) ($item['discount_percent'] ?? 0);
            $taxPercent = (float) ($item['tax_percent'] ?? 0);

            $lineTotal = $this->calculateLineTotal($quantity, $unitPrice, $discountPercent, $taxPercent);

            return [
                'product_id' => $item['product_id'] ?? null,
                'account_id' => (int) $item['account_id'],
                'description' => $item['description'],
                'quantity' => $quantity,
                'unit_id' => $item['unit_id'] ?? null,
                'unit_price' => $unitPrice,
                'discount_percent' => $discountPercent,
                'tax_percent' => $taxPercent,
                'line_total' => $lineTotal,
                'notes' => $item['notes'] ?? null,
            ];
        });

        $subtotal = $this->calculateSubtotal($normalized);
        $discountAmount = $this->calculateDiscountAmount($normalized);
        $taxAmount = $this->calculateTaxAmount($normalized);
        $grandTotal = $this->calculateGrandTotal($normalized);

        $amountReceived = (float) $customerInvoice->amount_received;
        $creditNoteAmount = (float) $customerInvoice->credit_note_amount;
        $amountDue = $grandTotal - $amountReceived - $creditNoteAmount;

        $customerInvoice->update([
            'subtotal' => (string) $subtotal,
            'discount_amount' => (string) $discountAmount,
            'tax_amount' => (string) $taxAmount,
            'grand_total' => (string) $grandTotal,
            'amount_due' => (string) $amountDue,
        ]);
    }
}
