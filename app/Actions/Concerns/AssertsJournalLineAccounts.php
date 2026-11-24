<?php

namespace App\Actions\Concerns;

use App\Actions\AccountingPosting\ResolveControlAccountAction;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

trait AssertsJournalLineAccounts
{
    /**
     * Toleransi perbandingan nominal (rupiah) untuk aturan akun kelompok.
     */
    private const GROUP_TOLERANCE = 0.005;

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

    /**
     * Akun kelompok (punya anak) hanya agregasi. Baris ke akun kelompok diterima hanya bila
     * memperkecil saldo posted akun itu (#437, keputusan putaran 4):
     * - B = saldo posted sekarang (SUM debit - credit semua jurnal posted, semua tahun),
     * - N = jumlah (debit - credit) seluruh baris jurnal ini untuk akun tersebut,
     * - diterima hanya bila N != 0, tanda N berlawanan dengan B, dan |N| <= |B|.
     *
     * Harus dipanggil di dalam DB::transaction. Baris akun kelompok dikunci (lockForUpdate)
     * sebelum B dibaca, sehingga dua posting bersamaan tidak sama-sama lolos.
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    protected function assertGroupAccountLinesReduceBalance(array $lines): void
    {
        $netByAccount = [];
        foreach ($lines as $line) {
            $accountId = (int) $line['account_id'];
            $netByAccount[$accountId] = ($netByAccount[$accountId] ?? 0.0)
                + (float) ($line['debit'] ?? 0)
                - (float) ($line['credit'] ?? 0);
        }

        if ($netByAccount === []) {
            return;
        }

        $groupAccounts = Account::query()
            ->whereIn('id', array_keys($netByAccount))
            ->whereHas('children')
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id', 'code']);

        foreach ($groupAccounts as $group) {
            $net = $netByAccount[$group->id];
            $balance = $this->postedNetBalance($group->id);

            if (! $this->reducesGroupBalance($balance, $net)) {
                throw ValidationException::withMessages([
                    'lines' => __('accounting_posting.line_group_account_not_reducing', [
                        'code' => $group->code,
                    ]),
                ]);
            }
        }
    }

    /**
     * Saldo posted bersih (debit - credit) satu akun, semua tahun fiskal.
     */
    private function postedNetBalance(int $accountId): float
    {
        $balance = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.status', 'posted')
            ->where('journal_entry_lines.account_id', $accountId)
            ->selectRaw('COALESCE(SUM(journal_entry_lines.debit - journal_entry_lines.credit), 0) AS balance')
            ->value('balance');

        return (float) $balance;
    }

    private function reducesGroupBalance(float $balance, float $net): bool
    {
        if (abs($balance) < self::GROUP_TOLERANCE || abs($net) < self::GROUP_TOLERANCE) {
            return false;
        }

        $oppositeSign = ($balance > 0 && $net < 0) || ($balance < 0 && $net > 0);

        return $oppositeSign && abs($net) <= abs($balance) + self::GROUP_TOLERANCE;
    }
}
