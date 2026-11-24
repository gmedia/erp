<?php

use App\Actions\Concerns\CalculatesTransactionLineTotals;

uses()->group('rounding-line-amounts');

function headerTotalsHelper(): object
{
    return new class
    {
        use CalculatesTransactionLineTotals;

        /**
         * @param  array<int, array<string, mixed>>  $items
         * @return array<string, string>
         */
        public function header(array $items): array
        {
            $normalized = array_map(function (array $row): array {
                $row['line_total'] = $this->calculateLineTotal(
                    (float) $row['quantity'],
                    (float) $row['unit_price'],
                    (float) ($row['discount_percent'] ?? 0),
                    (float) ($row['tax_percent'] ?? 0),
                );

                return $row;
            }, $items);

            return $this->calculateHeaderTotals($normalized);
        }
    };
}

test('header grand_total equals subtotal minus discount plus tax on fractional lines', function () {
    $header = headerTotalsHelper()->header([
        ['quantity' => 1, 'unit_price' => 0.284, 'discount_percent' => 2.5, 'tax_percent' => 11],
        ['quantity' => 5, 'unit_price' => 0.988, 'discount_percent' => 7.5, 'tax_percent' => 11],
    ]);

    $balanced = round(
        (float) $header['subtotal'] - (float) $header['discount_amount'] + (float) $header['tax_amount'],
        2,
    );

    expect((float) $header['grand_total'])->toBe($balanced);
});

test('header grand_total equals subtotal minus discount plus tax for a second fractional case', function () {
    $header = headerTotalsHelper()->header([
        ['quantity' => 8, 'unit_price' => 0.732, 'discount_percent' => 7.5, 'tax_percent' => 5.5],
        ['quantity' => 7, 'unit_price' => 0.15, 'discount_percent' => 7.5, 'tax_percent' => 5.5],
    ]);

    $balanced = round(
        (float) $header['subtotal'] - (float) $header['discount_amount'] + (float) $header['tax_amount'],
        2,
    );

    expect((float) $header['grand_total'])->toBe($balanced);
});
