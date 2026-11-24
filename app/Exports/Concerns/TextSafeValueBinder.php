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
 * sedangkan angka, bool, null, dan tanggal mengikuti perilaku default.
 */
class TextSafeValueBinder extends DefaultValueBinder
{
    /**
     * Awalan yang membuat pembaca spreadsheet menganggap teks sebagai formula atau
     * sel terformat. Dinetralkan dengan apostrof khusus CSV, karena CSV tidak punya
     * tipe sel.
     */
    private const DANGEROUS_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    private static bool $neutralizePrefixes = false;

    public static function neutralizeFormulaPrefixes(bool $enabled): void
    {
        self::$neutralizePrefixes = $enabled;
    }

    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value)) {
            $text = $this->sanitizeText($value);

            if (self::$neutralizePrefixes && $this->startsWithDangerousPrefix($text)) {
                $text = "'".$text;
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
