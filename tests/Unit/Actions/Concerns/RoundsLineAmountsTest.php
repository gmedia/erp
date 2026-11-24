<?php

use App\Actions\Concerns\RoundsLineAmounts;

uses()->group('rounding-line-amounts');

function roundingHelper(): object
{
    return new class
    {
        use RoundsLineAmounts;

        public function line(float|int|string $amount): float
        {
            return $this->roundLineAmount($amount);
        }

        /** @param  iterable<float|int|string>  $amounts */
        public function sum(iterable $amounts): float
        {
            return $this->sumRoundedLineAmounts($amounts);
        }
    };
}

test('roundLineAmount rounds a single line to 2 decimals', function () {
    $helper = roundingHelper();

    expect($helper->line('10.005'))->toBe(10.01)
        ->and($helper->line(10.004))->toBe(10.0)
        ->and($helper->line(3))->toBe(3.0);
});

test('sumRoundedLineAmounts sums per-line rounded amounts so header equals sum of lines', function () {
    $helper = roundingHelper();

    // 3 baris 0.005 : pembulatan per baris = 0.01 x3 = 0.03, bukan round(0.015) = 0.02
    expect($helper->sum([0.005, 0.005, 0.005]))->toBe(0.03);
});

test('property: header total equals sum of rounded lines for many fractional lines', function () {
    $helper = roundingHelper();
    mt_srand(437);

    for ($run = 0; $run < 200; $run++) {
        $count = mt_rand(1, 40);
        $lines = [];

        for ($i = 0; $i < $count; $i++) {
            $lines[] = mt_rand(1, 99999) / 1000; // pecahan hingga 3 desimal
        }

        $expected = 0.0;
        foreach ($lines as $line) {
            $expected += round($line, 2);
        }

        expect($helper->sum($lines))->toBe(round($expected, 2));
    }
});
