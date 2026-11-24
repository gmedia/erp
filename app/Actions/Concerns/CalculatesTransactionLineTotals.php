<?php

namespace App\Actions\Concerns;

trait CalculatesTransactionLineTotals
{
    use RoundsLineAmounts;

    protected function calculateLineTotal(
        float $quantity,
        float $unitPrice,
        float $discountPercent,
        float $taxPercent,
    ): float {
        $lineBeforeTax = $quantity * $unitPrice * (1 - ($discountPercent / 100));

        return $this->roundLineAmount($lineBeforeTax * (1 + ($taxPercent / 100)));
    }

    protected function calculateSubtotal(array $items): float
    {
        return $this->sumRoundedLineAmounts(array_map(
            static fn (array $row): float => (float) ($row['quantity'] * $row['unit_price']),
            $items,
        ));
    }

    protected function calculateDiscountAmount(array $items): float
    {
        return $this->sumRoundedLineAmounts(array_map(
            static fn (array $row): float => (float) (
                $row['quantity'] * $row['unit_price'] * (((float) ($row['discount_percent'] ?? 0)) / 100)
            ),
            $items,
        ));
    }

    protected function calculateTaxAmount(array $items): float
    {
        return $this->sumRoundedLineAmounts(array_map(
            static function (array $row): float {
                $lineSubtotal = (float) ($row['quantity'] * $row['unit_price']);
                $discountPercent = (float) ($row['discount_percent'] ?? 0);
                $discountedSubtotal = $lineSubtotal * (1 - ($discountPercent / 100));
                $taxPercent = (float) ($row['tax_percent'] ?? 0);

                return (float) ($discountedSubtotal * ($taxPercent / 100));
            },
            $items,
        ));
    }

    protected function calculateGrandTotal(array $items): float
    {
        return $this->sumRoundedLineAmounts(array_map(
            static fn (array $row): float => (float) $row['line_total'],
            $items,
        ));
    }

    /**
     * Header harus seimbang: grand_total = subtotal - diskon + pajak.
     * Selisih sen akibat pembulatan per komponen dibebankan sebagai penyeimbang:
     * ke pajak bila dokumen berpajak; bila pajak mentah 0, ke diskon; bila keduanya 0,
     * subtotal disamakan dengan grand total. Pajak dan diskon tidak boleh negatif.
     *
     * @param  array<int, array<string, mixed>>  $normalizedItems
     * @return array<string, string>
     */
    protected function calculateHeaderTotals(array $normalizedItems): array
    {
        $subtotal = $this->calculateSubtotal($normalizedItems);
        $discount = $this->calculateDiscountAmount($normalizedItems);
        $tax = $this->calculateTaxAmount($normalizedItems);
        $grandTotal = $this->calculateGrandTotal($normalizedItems);

        $balance = $this->roundLineAmount($grandTotal - $subtotal + $discount - $tax);

        if ($balance !== 0.0) {
            if ($tax > 0.0) {
                $tax = $this->roundLineAmount($tax + $balance);
            } elseif ($discount > 0.0 && $discount - $balance >= 0.0) {
                $discount = $this->roundLineAmount($discount - $balance);
            } else {
                $subtotal = $grandTotal;
            }
        }

        return [
            'subtotal' => (string) $subtotal,
            'discount_amount' => (string) $discount,
            'tax_amount' => (string) $tax,
            'grand_total' => (string) $grandTotal,
        ];
    }
}
