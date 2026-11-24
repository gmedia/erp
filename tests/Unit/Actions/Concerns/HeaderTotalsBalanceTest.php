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

        /**
         * Memakai line_total apa adanya (tanpa dihitung ulang), untuk kasus tepi penyeimbang.
         *
         * @param  array<int, array<string, mixed>>  $rows
         * @return array<string, string>
         */
        public function raw(array $rows): array
        {
            return $this->calculateHeaderTotals($rows);
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

test('header without tax puts the cent balancing difference on discount, not tax', function () {
    $header = headerTotalsHelper()->header([
        ['quantity' => 1, 'unit_price' => 0.284, 'discount_percent' => 2.5, 'tax_percent' => 0],
        ['quantity' => 5, 'unit_price' => 0.988, 'discount_percent' => 7.5, 'tax_percent' => 0],
    ]);

    expect((float) $header['tax_amount'])->toBe(0.0);
    expect((float) $header['discount_amount'])->toBeGreaterThanOrEqual(0.0);
    expect(round(
        (float) $header['subtotal'] - (float) $header['discount_amount'] + (float) $header['tax_amount'],
        2,
    ))->toBe((float) $header['grand_total']);
});

test('header without tax or discount keeps subtotal equal to grand total', function () {
    $header = headerTotalsHelper()->header([
        ['quantity' => 3, 'unit_price' => 0.333, 'discount_percent' => 0, 'tax_percent' => 0],
    ]);

    expect((float) $header['tax_amount'])->toBe(0.0);
    expect((float) $header['discount_amount'])->toBe(0.0);
    expect((float) $header['grand_total'])->toBe((float) $header['subtotal']);
});

test('last-resort balancing sets subtotal = grand_total + discount - tax when discount cannot absorb it', function () {
    // Subtotal 10,00; diskon 5% = 0,50; pajak 0; grand 10,80.
    // Selisih = 10,80 - 10,00 + 0,50 = 1,30. Diskon 0,50 tidak bisa menampung (hasil negatif),
    // dan pajak 0, jadi jatuh ke cabang terakhir: subtotal = 10,80 + 0,50 - 0 = 11,30.
    $header = headerTotalsHelper()->raw([
        ['quantity' => 1, 'unit_price' => 10.00, 'line_total' => 10.80, 'discount_percent' => 5, 'tax_percent' => 0],
    ]);

    expect((float) $header['grand_total'])->toBe(10.80);
    expect((float) $header['discount_amount'])->toBe(0.50);
    expect((float) $header['tax_amount'])->toBe(0.0);
    expect((float) $header['subtotal'])->toBe(11.30);
    expect(round(
        (float) $header['subtotal'] - (float) $header['discount_amount'] + (float) $header['tax_amount'],
        2,
    ))->toBe((float) $header['grand_total']);
});

test('balancing never makes tax negative', function () {
    // Subtotal 10,00; pajak 0,1% = 0,01; grand 9,00.
    // Selisih = 9,00 - 10,00 - 0,01 = -1,01. Pajak 0,01 tidak boleh menjadi -1,00,
    // diskon 0 tidak bisa menampung, jadi subtotal = 9,00 + 0 - 0,01 = 8,99 dan pajak tetap 0,01.
    $header = headerTotalsHelper()->raw([
        ['quantity' => 1, 'unit_price' => 10.00, 'line_total' => 9.00, 'discount_percent' => 0, 'tax_percent' => 0.1],
    ]);

    expect((float) $header['tax_amount'])->toBe(0.01);
    expect((float) $header['discount_amount'])->toBe(0.0);
    expect((float) $header['subtotal'])->toBe(8.99);
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
