<?php

namespace App\Exports\Concerns;

use App\Models\Setting;

/**
 * Format sel Excel untuk kolom angka dan mata uang.
 *
 * Nilai sel tetap angka mentah (bisa dihitung ulang di Excel). Format sel memakai
 * mata uang dari Setting perusahaan (`currency`). Pemisah ribuan dan desimal mengikuti
 * locale Excel pengguna: format code OOXML selalu memakai `#,##0.00` (invarian), dan
 * mengubah pemisah lewat format sel akan merusak pengelompokan ribuan.
 *
 * Kelas yang memakai trait ini mendeklarasikan kolom lewat `numericColumnHeadings()` /
 * `currencyColumnHeadings()` dan mengimplementasi WithColumnFormatting bila formatnya dipakai.
 */
trait ExportsNumberFormats
{
    private const NUMBER_FORMAT = '#,##0.00';

    /**
     * Nama header kolom yang berisi angka biasa (mis. 'Quantity On Hand').
     *
     * @return array<int, string>
     */
    protected function numericColumnHeadings(): array
    {
        return [];
    }

    /**
     * Nama header kolom yang berisi nilai mata uang (mis. 'Cost').
     *
     * @return array<int, string>
     */
    protected function currencyColumnHeadings(): array
    {
        return [];
    }

    /**
     * @param  array<int, string>  $headings  urutan header sesuai columns()
     * @return array<string, string> huruf kolom => pola format Excel
     */
    protected function buildColumnFormats(array $headings): array
    {
        $currencyFormat = $this->excelCurrencyFormat();
        $formats = [];

        foreach ($headings as $index => $heading) {
            $letter = $this->columnLetter($index + 1);

            if (in_array($heading, $this->currencyColumnHeadings(), true)) {
                $formats[$letter] = $currencyFormat;
            } elseif (in_array($heading, $this->numericColumnHeadings(), true)) {
                $formats[$letter] = self::NUMBER_FORMAT;
            }
        }

        return $formats;
    }

    /**
     * Pola mata uang: kode dari Setting `currency` sebagai literal di depan angka.
     * Bila Setting `currency` kosong, memakai format angka biasa tanpa simbol.
     */
    protected function excelCurrencyFormat(): string
    {
        $code = $this->regionalCurrencyCode();

        return $code === null ? self::NUMBER_FORMAT : '"' . $code . '" ' . self::NUMBER_FORMAT;
    }

    /**
     * Kode mata uang perusahaan dari Setting `currency`, atau null bila belum diisi.
     */
    protected function regionalCurrencyCode(): ?string
    {
        $raw = (string) Setting::get('currency');
        $code = preg_replace('/[^A-Z]/', '', strtoupper(trim($raw)));

        return $code !== '' ? $code : null;
    }

    /**
     * Nilai angka mentah untuk sel. Cast decimal Eloquent berupa string ("500.00") diubah ke float
     * agar sel tersimpan sebagai angka, bukan teks. Null tetap null.
     */
    protected function numericValue(mixed $value): int|float|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function columnLetter(int $position): string
    {
        $letter = '';

        while ($position > 0) {
            $remainder = ($position - 1) % 26;
            $letter = chr(65 + $remainder) . $letter;
            $position = intdiv($position - 1, 26);
        }

        return $letter;
    }
}
