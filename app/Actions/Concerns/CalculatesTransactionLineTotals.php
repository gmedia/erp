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
     * @param  array<int, array<string, mixed>>  $normalizedItems
     * @return array<string, string>
     */
    protected function calculateHeaderTotals(array $normalizedItems): array
    {
        return [
            'subtotal' => (string) $this->calculateSubtotal($normalizedItems),
            'discount_amount' => (string) $this->calculateDiscountAmount($normalizedItems),
            'tax_amount' => (string) $this->calculateTaxAmount($normalizedItems),
            'grand_total' => (string) $this->calculateGrandTotal($normalizedItems),
        ];
    }
}
