<?php

namespace App\Actions\PeriodClosings;

use App\Actions\AccountBalances\RecalculateAccountBalancesAction;
use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\JournalEntry;
use App\Models\PeriodClosing;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClosePeriodAction
{
    public function __construct(private RecalculateAccountBalancesAction $recalculate) {}

    public function execute(PeriodClosing $periodClosing): PeriodClosing
    {
        return DB::transaction(function () use ($periodClosing): PeriodClosing {
            if ($periodClosing->isClosed()) {
                throw ValidationException::withMessages(['status' => 'Period is already closed.']);
            }

            $month = $periodClosing->period_month ?? 12;
            $this->recalculate->execute($periodClosing->fiscal_year_id, $month, $periodClosing->period_year);
            $entry = $periodClosing->isAnnual() ? $this->createAnnualClosingEntry($periodClosing) : null;

            $periodClosing->update(['status' => 'closed', 'closed_by' => auth()->id(), 'closed_at' => now(), 'closing_journal_entry_id' => $entry?->id]);

            return $periodClosing->refresh()->load(['fiscalYear', 'closingJournalEntry', 'retainedEarningsAccount', 'closedBy', 'reopenedBy', 'creator']);
        });
    }

    private function createAnnualClosingEntry(PeriodClosing $periodClosing): JournalEntry
    {
        $accounts = Account::query()->whereIn('type', ['revenue', 'expense'])->get();
        $entry = JournalEntry::create(['fiscal_year_id' => $periodClosing->fiscal_year_id, 'entry_number' => 'CL-' . now()->format('YmdHis'), 'entry_date' => now()->toDateString(), 'reference' => 'Period Closing #' . $periodClosing->id, 'description' => 'Annual closing entry', 'status' => 'posted', 'journal_type' => 'closing', 'source_type' => PeriodClosing::class, 'source_id' => $periodClosing->id, 'created_by' => auth()->id(), 'posted_by' => auth()->id(), 'posted_at' => now()]);
        $netIncome = 0.0;

        foreach ($accounts as $account) {
            $balance = (float) AccountBalance::query()->where('account_id', $account->id)->where('fiscal_year_id', $periodClosing->fiscal_year_id)->where('period_year', $periodClosing->period_year)->orderByDesc('period_month')->value('closing_balance');
            if ($balance == 0.0) {
                continue;
            }
            $netIncome += $account->type === 'revenue' ? $balance : -$balance;
            $entry->lines()->create(['account_id' => $account->id, 'debit' => $account->type === 'revenue' ? abs($balance) : 0, 'credit' => $account->type === 'expense' ? abs($balance) : 0, 'memo' => 'Close ' . $account->name]);
        }

        $entry->lines()->create(['account_id' => $periodClosing->retained_earnings_account_id, 'debit' => $netIncome < 0 ? abs($netIncome) : 0, 'credit' => $netIncome > 0 ? $netIncome : 0, 'memo' => 'Close net income to retained earnings']);
        $periodClosing->update(['net_income' => $netIncome]);

        return $entry;
    }
}
