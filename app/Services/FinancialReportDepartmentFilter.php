<?php

namespace App\Services;

/**
 * Aturan filter departemen pada laporan keuangan.
 *
 * Nilai `department_id` berupa id departemen, atau NONE untuk baris jurnal tanpa departemen.
 */
final class FinancialReportDepartmentFilter
{
    public const NONE = 'none';

    public static function departmentId(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === self::NONE) {
            return null;
        }

        return (int) $value;
    }

    public static function withoutDepartment(mixed $value): bool
    {
        return $value === self::NONE;
    }
}
