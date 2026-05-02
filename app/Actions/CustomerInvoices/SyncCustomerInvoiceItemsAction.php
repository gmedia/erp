<?php

namespace App\Actions\CustomerInvoices;

use App\Actions\Concerns\RecreatesItems;
use App\Models\CustomerInvoice;

class SyncCustomerInvoiceItemsAction
{
    use RecreatesItems;

    public function execute(CustomerInvoice $customerInvoice, array $items): void
    {
        $normalized = $this->recreateItems($customerInvoice->items(), $items, static function (array $item): array {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $discountPercent = (float) ($item['discount_percent'] ?? 0);
            $taxPercent = (float) ($item['tax_percent'] ?? 0);

            $lineBeforeTax = $quantity * $unitPrice * (1 - ($discountPercent / 100));
            $lineTotal = $lineBeforeTax * (1 + ($taxPercent / 100));

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
