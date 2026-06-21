<?php

namespace App\Services;

use App\Models\Account;
use App\Models\CoaVersion;
use RuntimeException;

class InterBranchClearingService
{
    public const CLEARING_CODE = '1999-IBC';

    public const CLEARING_MEMO = 'Inter-branch clearing (auto)';

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return list<array<string, mixed>>
     */
    public function inject(array $lines, ?int $clearingAccountId): array
    {
        if ($clearingAccountId !== null) {
            $lines = array_values(array_filter(
                $lines,
                fn (array $line): bool => ($line['account_id'] ?? null) !== $clearingAccountId,
            ));
        }

        $netByBranch = [];
        $hasNullBranch = false;
        foreach ($lines as $line) {
            $branchId = $line['branch_id'] ?? null;
            $key = $branchId === null ? '__null__' : (string) $branchId;
            if ($branchId === null) {
                $hasNullBranch = true;
            }
            $netByBranch[$key] = ($netByBranch[$key] ?? 0)
                + $this->toCents($line['debit'] ?? 0)
                - $this->toCents($line['credit'] ?? 0);
        }

        if (count($netByBranch) <= 1) {
            return $lines;
        }

        $branchIds = array_map('intval', array_keys($netByBranch));
        sort($branchIds);

        $needsInjection = false;
        foreach ($netByBranch as $net) {
            if ($net !== 0) {
                $needsInjection = true;
                break;
            }
        }

        if (! $needsInjection) {
            return $lines;
        }

        if ($hasNullBranch) {
            throw new RuntimeException(
                'Cannot inject inter-branch clearing: a journal line has an unresolved branch '
                . 'while the entry spans multiple branches.',
            );
        }

        if ($clearingAccountId === null) {
            throw new RuntimeException(
                'Cannot inject inter-branch clearing: clearing account (' . self::CLEARING_CODE . ') '
                . 'is not configured for this fiscal year.',
            );
        }

        $injected = [];
        foreach ($branchIds as $branchId) {
            $net = $netByBranch[(string) $branchId];
            if ($net === 0) {
                continue;
            }

            $injected[] = [
                'account_id' => $clearingAccountId,
                'branch_id' => $branchId,
                'debit' => $net < 0 ? $this->fromCents(-$net) : '0.00',
                'credit' => $net > 0 ? $this->fromCents($net) : '0.00',
                'memo' => self::CLEARING_MEMO,
            ];
        }

        return array_merge($lines, $injected);
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    public function assertBalancedPerBranch(array $lines): void
    {
        $netByBranch = [];
        foreach ($lines as $line) {
            $branchId = $line['branch_id'] ?? null;
            $key = $branchId === null ? '__null__' : (string) $branchId;
            $netByBranch[$key] = ($netByBranch[$key] ?? 0)
                + $this->toCents($line['debit'] ?? 0)
                - $this->toCents($line['credit'] ?? 0);
        }

        foreach ($netByBranch as $key => $net) {
            if ($net !== 0) {
                throw new RuntimeException(
                    "Per-branch balance guard failed for branch [{$key}]: net {$net} cents.",
                );
            }
        }
    }

    public function resolveAccountIdForFiscalYear(int $fiscalYearId): ?int
    {
        $coaVersion = CoaVersion::query()
            ->where('fiscal_year_id', $fiscalYearId)
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'archived' THEN 1 WHEN 'draft' THEN 2 ELSE 3 END")
            ->orderByDesc('id')
            ->first();

        if (! $coaVersion) {
            return null;
        }

        $id = Account::query()
            ->where('coa_version_id', $coaVersion->id)
            ->where('code', self::CLEARING_CODE)
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    private function toCents(mixed $value): int
    {
        return (int) round(((float) $value) * 100);
    }

    private function fromCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
