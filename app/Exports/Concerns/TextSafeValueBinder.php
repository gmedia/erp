<?php

namespace App\Exports\Concerns;

use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

/**
 * Value binder untuk ekspor: nilai bertipe string selalu ditulis sebagai teks.
 *
 * Binder default PhpSpreadsheet menentukan tipe sel dari isi string (mis. awalan "="
 * menjadi formula). Binder ini menentukan tipe dari tipe PHP: string tetap string,
 * kecuali string yang berbentuk angka desimal valid (ditulis sebagai angka), sedangkan
 * angka, bool, null, dan tanggal mengikuti perilaku default.
 */
class TextSafeValueBinder extends DefaultValueBinder
{
    private static bool $neutralizePrefixes = false;

    /**
     * Angka desimal dari string (mis. hasil cast decimal Eloquent "1500000.00") tetap
     * ditulis sebagai angka. Nol di depan, awalan "+", dan notasi eksponen tetap teks.
     */
    private const NUMERIC_PATTERN = '/^-?(0|[1-9][0-9]{0,14})(\.[0-9]+)?$/';

    /**
     * Awalan yang membuat pembaca spreadsheet menganggap teks sebagai formula atau
     * sel terformat. Dinetralkan dengan apostrof khusus CSV, karena CSV tidak punya
     * tipe sel.
     */
    private const DANGEROUS_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    public static function neutralizeFormulaPrefixes(bool $enabled): void
    {
        self::$neutralizePrefixes = $enabled;
    }

    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value) && preg_match(self::NUMERIC_PATTERN, $value) === 1) {
            $number = str_contains($value, '.') ? (float) $value : (int) $value;

            return parent::bindValue($cell, $number);
        }

        if (is_string($value)) {
            $text = $this->sanitizeText($value);

            if (self::$neutralizePrefixes && $this->startsWithDangerousPrefix($text)) {
                $text = "'" . $text;
            }

            $cell->setValueExplicit($text, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    private function sanitizeText(string $value): string
    {
        return DataType::checkString(StringHelper::sanitizeUTF8($value));
    }

    private function startsWithDangerousPrefix(string $text): bool
    {
        foreach (self::DANGEROUS_PREFIXES as $prefix) {
            if (str_starts_with($text, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
