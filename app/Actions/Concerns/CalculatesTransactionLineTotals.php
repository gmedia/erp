<?php

namespace App\Actions\Concerns;

trait CalculatesTransactionLineTotals
{
    protected function calculateLineTotal(float $quantity, float $unitPrice, float $discountPercent, float $taxPercent): float
    {
        $lineBeforeTax = $quantity * $unitPrice * (1 - ($discountPercent / 100));

        return $lineBeforeTax * (1 + ($taxPercent / 100));
    }

    protected function calculateSubtotal(array $items): float
    {
        return (float) collect($items)
            ->sum(static fn (array $row) => (float) ($row['quantity'] * $row['unit_price']));
    }

    protected function calculateDiscountAmount(array $items): float
    {
        return (float) collect($items)
            ->sum(static function (array $row): float {
                $lineSubtotal = (float) ($row['quantity'] * $row['unit_price']);
                $discountPercent = (float) ($row['discount_percent'] ?? 0);

                return (float) ($lineSubtotal * ($discountPercent / 100));
            });
    }

    protected function calculateTaxAmount(array $items): float
    {
        return (float) collect($items)
            ->sum(static function (array $row): float {
                $lineSubtotal = (float) ($row['quantity'] * $row['unit_price']);
                $discountPercent = (float) ($row['discount_percent'] ?? 0);
                $discountedSubtotal = $lineSubtotal * (1 - ($discountPercent / 100));
                $taxPercent = (float) ($row['tax_percent'] ?? 0);

                return (float) ($discountedSubtotal * ($taxPercent / 100));
            });
    }

    protected function calculateGrandTotal(array $items): float
    {
        return (float) collect($items)->sum(static fn (array $row) => (float) ($row['line_total']));
    }

    /**
     * @param  array<int, array<string, mixed>>  $normalizedItems
     * @return array<string, string>
     */
    protected function calculateHeaderTotals(array $normalizedItems): array
    {
        $subtotal = collect($normalizedItems)
            ->sum(static fn (array $row) => (float) ($row['quantity'] * $row['unit_price']));

        $discountAmount = collect($normalizedItems)
            ->sum(static function (array $row): float {
                $lineSubtotal = (float) ($row['quantity'] * $row['unit_price']);

                return (float) ($lineSubtotal * (($row['discount_percent'] ?? 0) / 100));
            });

        $taxAmount = collect($normalizedItems)
            ->sum(static function (array $row): float {
                $lineSubtotal = (float) ($row['quantity'] * $row['unit_price']);
                $discountedSubtotal = $lineSubtotal * (1 - (($row['discount_percent'] ?? 0) / 100));

                return (float) ($discountedSubtotal * (($row['tax_percent'] ?? 0) / 100));
            });

        $grandTotal = collect($normalizedItems)->sum(static fn (array $row) => (float) ($row['line_total']));

        return [
            'subtotal' => (string) $subtotal,
            'discount_amount' => (string) $discountAmount,
            'tax_amount' => (string) $taxAmount,
            'grand_total' => (string) $grandTotal,
        ];
    }
}
