<?php

namespace App\Actions\Reports;

use App\Models\AccountBalance;

class GetTrialBalanceReportAction
{
    public function execute(array $filters): array
    {
        $rows = AccountBalance::query()
            ->with('account')
            ->where('fiscal_year_id', $filters['fiscal_year_id'])
            ->where('period_month', $filters['period_month'])
            ->where('period_year', $filters['period_year'])
            ->get()
            ->map(fn (AccountBalance $balance): array => $this->row($balance));

        $totalDebit = $rows->sum('debit_balance');
        $totalCredit = $rows->sum('credit_balance');

        return ['data' => $rows, 'summary' => ['total_debit' => $totalDebit, 'total_credit' => $totalCredit, 'is_balanced' => bccomp((string) $totalDebit, (string) $totalCredit, 2) === 0]];
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
