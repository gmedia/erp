<?php

namespace App\Actions\Concerns;

use App\Actions\AccountingPosting\ResolveControlAccountAction;
use App\Models\Account;
use Illuminate\Validation\ValidationException;

trait AssertsJournalLineAccounts
{
    /**
     * Jurnal posted hanya boleh memakai akun aktif dari COA version yang berlaku untuk
     * tanggal jurnal (#357). Akun COA archived, nonaktif, atau dari COA lain ditolak 422
     * agar tidak menggandakan saldo lama atau mencampur dua COA dalam satu jurnal.
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    protected function assertLinesUseActiveAccounts(array $lines, string $entryDate): void
    {
        $coaVersionId = app(ResolveControlAccountAction::class)->resolveVersion($entryDate)->id;

        $accountIds = array_values(array_unique(array_map(
            fn (array $line): int => (int) $line['account_id'],
            $lines,
        )));

        $accounts = Account::query()
            ->whereIn('id', $accountIds)
            ->get(['id', 'code', 'coa_version_id', 'is_active']);

        if ($accounts->count() !== count($accountIds)) {
            throw ValidationException::withMessages([
                'lines' => __('accounting_posting.line_account_outside_coa', [
                    'code' => '#' . implode(',#', $accountIds),
                    'date' => $entryDate,
                ]),
            ]);
        }

        foreach ($accounts as $account) {
            if (! $account->is_active || $account->coa_version_id !== $coaVersionId) {
                throw ValidationException::withMessages([
                    'lines' => __('accounting_posting.line_account_outside_coa', [
                        'code' => $account->code,
                        'date' => $entryDate,
                    ]),
                ]);
            }
        }
    }
}
