<?php

namespace App\Actions\AccountBalances;

use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\JournalEntryLine;

class RecalculateAccountBalancesAction
{
    public function execute(int $fiscalYearId, int $periodMonth, int $periodYear): void
    {
        Account::query()->each(function (Account $account) use ($fiscalYearId, $periodMonth, $periodYear): void {
            $opening = AccountBalance::query()
                ->where('account_id', $account->id)
                ->where('fiscal_year_id', $fiscalYearId)
                ->where(function ($query) use ($periodMonth, $periodYear): void {
                    $query->where('period_year', '<', $periodYear)
                        ->orWhere(fn ($q) => $q->where('period_year', $periodYear)->where('period_month', '<', $periodMonth));
                })
                ->orderByDesc('period_year')
                ->orderByDesc('period_month')
                ->value('closing_balance') ?? 0;

            $totals = JournalEntryLine::query()
                ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.fiscal_year_id', $fiscalYearId)
                ->where('journal_entries.status', 'posted')
                ->where('journal_entry_lines.account_id', $account->id)
                ->whereYear('journal_entries.entry_date', $periodYear)
                ->whereMonth('journal_entries.entry_date', $periodMonth)
                ->selectRaw('COALESCE(SUM(debit), 0) as debit_total, COALESCE(SUM(credit), 0) as credit_total')
                ->first();

            $debit = (float) ($totals->debit_total ?? 0);
            $credit = (float) ($totals->credit_total ?? 0);
            $movement = $account->normal_balance === 'credit' ? $credit - $debit : $debit - $credit;

            AccountBalance::updateOrCreate(
                ['account_id' => $account->id, 'fiscal_year_id' => $fiscalYearId, 'period_month' => $periodMonth, 'period_year' => $periodYear],
                ['opening_balance' => $opening, 'debit_total' => $debit, 'credit_total' => $credit, 'movement' => $movement, 'closing_balance' => (float) $opening + $movement, 'last_recalculated_at' => now()],
            );
        });
    }
}
