<?php

namespace App\Actions\CreditNotes;

use App\Actions\Concerns\CalculatesTransactionLineTotals;
use App\Actions\Concerns\RecreatesItems;
use App\Models\CreditNote;

class SyncCreditNoteItemsAction
{
    use CalculatesTransactionLineTotals;
    use RecreatesItems;

    public function execute(CreditNote $creditNote, array $items): void
    {
        $normalized = $this->recreateItems($creditNote->items(), $items, function (array $item): array {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $taxPercent = (float) ($item['tax_percent'] ?? 0);

            $lineTotal = $this->calculateLineTotal($quantity, $unitPrice, 0, $taxPercent);

            return [
                'product_id' => $item['product_id'] ?? null,
                'account_id' => (int) $item['account_id'],
                'description' => $item['description'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'tax_percent' => $taxPercent,
                'line_total' => $lineTotal,
                'notes' => $item['notes'] ?? null,
            ];
        });

        $subtotal = $this->calculateSubtotal($normalized);
        $taxAmount = $this->calculateTaxAmount($normalized);
        $grandTotal = $this->calculateGrandTotal($normalized);

        $creditNote->update([
            'subtotal' => (string) $subtotal,
            'tax_amount' => (string) $taxAmount,
            'grand_total' => (string) $grandTotal,
        ]);
    }
}
