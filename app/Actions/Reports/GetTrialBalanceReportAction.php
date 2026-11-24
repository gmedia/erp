<?php

namespace App\Actions\Reports;

use App\Actions\FiscalYears\GetPreferredFiscalYearAction;
use App\Models\AccountBalance;
use App\Models\FiscalYear;
use Illuminate\Support\Collection;

class GetTrialBalanceReportAction
{
    public function execute(array $filters): array
    {
        $fiscalYearId = $this->resolveFiscalYearId($filters);

        if ($fiscalYearId === null) {
            return [
                'data' => [],
                'summary' => [
                    'total_debit' => 0,
                    'total_credit' => 0,
                    'is_balanced' => true,
                ],
            ];
        }

        $periodMonth = $this->intOrNull($filters['period_month'] ?? null);
        $periodYear = $this->intOrNull($filters['period_year'] ?? null);
        $periodSelected = $periodMonth !== null || $periodYear !== null;

        $query = AccountBalance::query()
            ->with('account')
            ->where('fiscal_year_id', $fiscalYearId);

        if ($periodSelected) {
            if ($periodYear === null) {
                $periodYear = FiscalYear::query()
                    ->whereKey($fiscalYearId)
                    ->value('start_date')?->year;
            }

            if ($periodMonth !== null) {
                $query->where('period_month', $periodMonth);
            }

            if ($periodYear !== null) {
                $query->where('period_year', $periodYear);
            }
        }

        $balances = $query->get();

        $rows = ($periodSelected
            ? $balances
            : $balances->groupBy('account_id')->map(
                fn ($group) => $this->mergeBalances($group),
            )->values())
            ->map(fn (AccountBalance $balance): array => $this->row($balance));

        $totalDebit = $rows->sum('debit_balance');
        $totalCredit = $rows->sum('credit_balance');

        return [
            'data' => $rows,
            'summary' => [
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'is_balanced' => bccomp(
                    (string) $totalDebit,
                    (string) $totalCredit,
                    2,
                ) === 0,
            ],
        ];
    }

    private function resolveFiscalYearId(array $filters): ?int
    {
        if (isset($filters['fiscal_year_id']) && $filters['fiscal_year_id'] !== '') {
            return (int) $filters['fiscal_year_id'];
        }

        $fiscalYears = FiscalYear::query()->orderByDesc('start_date')->get();

        $preferred = app(GetPreferredFiscalYearAction::class)->execute($fiscalYears);

        return $preferred?->id;
    }

    private function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        return (int) $value;
    }

    /**
     * Gabungkan saldo beberapa periode menjadi satu baris per akun (dipakai
     * saat periode tidak dipilih, agar akun tidak tampil berulang — W4-20).
     *
     * @param  Collection<int, AccountBalance>  $group
     */
    private function mergeBalances($group): AccountBalance
    {
        $first = $group->first();
        $first->opening_balance = $group->sum('opening_balance');
        $first->debit_total = $group->sum('debit_total');
        $first->credit_total = $group->sum('credit_total');
        $first->closing_balance = $group->sum('closing_balance');

        return $first;
    }

    private function row(AccountBalance $balance): array
    {
        // closing_balance memakai konvensi normal balance akun. Saldo positif berarti
        // searah normal balance, jadi untuk akun kredit-normal saldo positif = kolom kredit.
        $closing = (float) $balance->closing_balance;
        $isCreditNormal = $balance->account->normal_balance === 'credit';
        $debitBalance = $isCreditNormal ? -$closing : $closing;

        return [
            'account_id' => $balance->account_id,
            'account_code' => $balance->account->code,
            'account_name' => $balance->account->name,
            'account_type' => $balance->account->type,
            'period_month' => $balance->period_month,
            'period_year' => $balance->period_year,
            'opening_balance' => (float) $balance->opening_balance,
            'debit_total' => (float) $balance->debit_total,
            'credit_total' => (float) $balance->credit_total,
            'closing_balance' => $closing,
            'debit_balance' => $debitBalance >= 0 ? $debitBalance : 0,
            'credit_balance' => $debitBalance < 0 ? abs($debitBalance) : 0,
        ];
    }
}
