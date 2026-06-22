<?php

namespace App\Actions\Reports;

use App\Actions\FiscalYears\GetPreferredFiscalYearAction;
use App\Models\AccountBalance;
use App\Models\FiscalYear;

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

        if ($periodYear === null) {
            $periodYear = FiscalYear::query()
                ->whereKey($fiscalYearId)
                ->value('start_date')?->year;
        }

        $query = AccountBalance::query()
            ->with('account')
            ->where('fiscal_year_id', $fiscalYearId);

        if ($periodMonth !== null) {
            $query->where('period_month', $periodMonth);
        }

        if ($periodYear !== null) {
            $query->where('period_year', $periodYear);
        }

        $rows = $query->get()
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
        if (isset($filters['fiscal_year_id']) && $filters['fiscal_year_id'] !== null && $filters['fiscal_year_id'] !== '') {
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

    private function row(AccountBalance $balance): array
    {
        $closing = (float) $balance->closing_balance;

        return [
            'account_id' => $balance->account_id,
            'account_code' => $balance->account->code,
            'account_name' => $balance->account->name,
            'account_type' => $balance->account->type,
            'opening_balance' => (float) $balance->opening_balance,
            'debit_total' => (float) $balance->debit_total,
            'credit_total' => (float) $balance->credit_total,
            'closing_balance' => $closing,
            'debit_balance' => $closing >= 0 ? $closing : 0,
            'credit_balance' => $closing < 0 ? abs($closing) : 0,
        ];
    }
}
