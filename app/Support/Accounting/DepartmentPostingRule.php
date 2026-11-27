<?php

namespace App\Support\Accounting;

use App\Models\Account;

/**
 * Satu sumber keputusan (#563): dimensi departemen hanya melekat pada baris jurnal
 * akun pendapatan (revenue) dan beban (expense). Akun aset, kewajiban, dan ekuitas
 * selalu tanpa departemen. Dipakai oleh posting dokumen dan validasi jurnal manual.
 */
final class DepartmentPostingRule
{
    /** @var list<string> */
    public const DEPARTMENT_ACCOUNT_TYPES = ['revenue', 'expense'];

    /**
     * Departemen yang boleh dibawa baris jurnal untuk akun ini.
     */
    public static function appliesTo(?string $accountType): bool
    {
        return in_array($accountType, self::DEPARTMENT_ACCOUNT_TYPES, true);
    }

    /**
     * Departemen efektif untuk baris akun ini: departemen dokumen bila akun pendapatan/beban,
     * selain itu null.
     */
    public static function resolve(?int $departmentId, ?string $accountType): ?int
    {
        return self::appliesTo($accountType) ? $departmentId : null;
    }

    /**
     * Map account_id => type untuk sekumpulan akun, sekali query.
     *
     * @param  iterable<int>  $accountIds
     * @return array<int, string>
     */
    public static function accountTypesById(iterable $accountIds): array
    {
        $ids = array_values(array_unique(array_map('intval', [...$accountIds])));

        if ($ids === []) {
            return [];
        }

        return Account::query()
            ->whereIn('id', $ids)
            ->pluck('type', 'id')
            ->map(static fn ($type): string => (string) $type)
            ->all();
    }
}
