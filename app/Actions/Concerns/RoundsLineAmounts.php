<?php

namespace App\Actions\Concerns;

/**
 * Pembulatan uang per baris, lalu dijumlah. Header dokumen, baris, dan jurnal
 * harus memakai helper ini supaya selisih 0,01 tidak muncul pada banyak baris pecahan.
 */
trait RoundsLineAmounts
{
    protected function roundLineAmount(float|int|string $amount): float
    {
        return round((float) $amount, 2);
    }

    /**
     * @param  iterable<float|int|string>  $amounts  nilai baris mentah (belum dibulatkan)
     */
    protected function sumRoundedLineAmounts(iterable $amounts): float
    {
        $total = 0.0;

        foreach ($amounts as $amount) {
            $total += $this->roundLineAmount($amount);
        }

        return round($total, 2);
    }
}
