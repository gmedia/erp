<?php

namespace App\Actions\Concerns;

trait CalculatesTransactionLineTotals
{
    protected function calculateLineTotal(
        float $quantity,
        float $unitPrice,
        float $discountPercent,
        float $taxPercent
    ): float {
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
}
