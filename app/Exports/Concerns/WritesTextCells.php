<?php

namespace App\Exports\Concerns;

use PhpOffice\PhpSpreadsheet\Cell\Cell;

/**
 * Menerapkan TextSafeValueBinder ke sebuah kelas ekspor.
 * Titik bersama untuk semua ekspor xlsx/csv: nilai teks tidak pernah menjadi formula.
 * Kelas yang memakai trait ini wajib mengimplementasi WithCustomValueBinder.
 */
trait WritesTextCells
{
    public function bindValue(Cell $cell, $value): bool
    {
        return (new TextSafeValueBinder)->bindValue($cell, $value);
    }
}
