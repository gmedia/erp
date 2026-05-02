<?php

namespace App\Actions\CreditNotes;

use App\Actions\Concerns\RecreatesItems;
use App\Models\CreditNote;

class SyncCreditNoteItemsAction
{
    use RecreatesItems;

    public function execute(CreditNote $creditNote, array $items): void
    {
        $normalized = $this->recreateItems($creditNote->items(), $items, static function (array $item): array {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $taxPercent = (float) ($item['tax_percent'] ?? 0);

            $subtotal = $quantity * $unitPrice;
            $tax = $subtotal * ($taxPercent / 100);
            $lineTotal = $subtotal + $tax;

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

        $subtotal = collect($normalized)
            ->sum(static fn (array $row) => (float) ($row['quantity'] * $row['unit_price']));
        $taxAmount = collect($normalized)
            ->sum(static function (array $row): float {
                $lineSubtotal = (float) ($row['quantity'] * $row['unit_price']);

                return (float) ($lineSubtotal * ($row['tax_percent'] / 100));
            });
        $grandTotal = collect($normalized)->sum(static fn (array $row) => (float) ($row['line_total']));

        $creditNote->update([
            'subtotal' => (string) $subtotal,
            'tax_amount' => (string) $taxAmount,
            'grand_total' => (string) $grandTotal,
        ]);
    }
}
