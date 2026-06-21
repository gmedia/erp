<?php

namespace App\Actions\PeriodClosings;

use App\Actions\AccountBalances\RecalculateAccountBalancesAction;
use App\Models\JournalEntry;
use App\Models\PeriodClosing;
use App\Services\InterBranchClearingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClosePeriodAction
{
    public function __construct(
        private RecalculateAccountBalancesAction $recalculate,
        private InterBranchClearingService $clearing,
    ) {}

    public function execute(PeriodClosing $periodClosing): PeriodClosing
    {
        return DB::transaction(function () use ($periodClosing): PeriodClosing {
            if ($periodClosing->isClosed()) {
                throw ValidationException::withMessages([
                    'status' => 'Period is already closed.',
                ]);
            }

            if ($periodClosing->closing_journal_entry_id) {
                JournalEntry::where('id', $periodClosing->closing_journal_entry_id)->delete();
                $periodClosing->update(['closing_journal_entry_id' => null]);
            }

            $month = $periodClosing->period_month ?? 12;
            $this->recalculate->execute(
                $periodClosing->fiscal_year_id,
                $month,
                $periodClosing->period_year,
            );
            $entry = $periodClosing->isAnnual()
                ? $this->createAnnualClosingEntry($periodClosing)
                : null;

            $periodClosing->update([
                'status' => 'closed',
                'closed_by' => auth()->id(),
                'closed_at' => now(),
                'closing_journal_entry_id' => $entry?->id,
            ]);

            return $periodClosing->refresh()->load([
                'fiscalYear',
                'closingJournalEntry',
                'retainedEarningsAccount',
                'closedBy',
                'reopenedBy',
                'creator',
            ]);
        });
    }

    private function createAnnualClosingEntry(PeriodClosing $periodClosing): JournalEntry
    {
        $entry = JournalEntry::create([
            'fiscal_year_id' => $periodClosing->fiscal_year_id,
            'entry_number' => 'CL-' . now()->format('YmdHis') . '-' . $periodClosing->id,
            'entry_date' => now()->toDateString(),
            'reference' => 'Period Closing #' . $periodClosing->id,
            'description' => 'Annual closing entry',
            'status' => 'posted',
            'journal_type' => 'closing',
            'source_type' => PeriodClosing::class,
            'source_id' => $periodClosing->id,
            'created_by' => auth()->id(),
            'posted_by' => auth()->id(),
            'posted_at' => now(),
        ]);

        $rows = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entries.fiscal_year_id', $periodClosing->fiscal_year_id)
            ->where('journal_entries.status', 'posted')
            ->where('journal_entries.journal_type', '<>', 'closing')
            ->whereIn('accounts.type', ['revenue', 'expense'])
            ->groupBy('journal_entry_lines.account_id', 'journal_entry_lines.branch_id', 'accounts.name')
            ->select('journal_entry_lines.account_id', 'journal_entry_lines.branch_id', 'accounts.name')
            ->selectRaw('COALESCE(SUM(journal_entry_lines.debit), 0) as debit_total')
            ->selectRaw('COALESCE(SUM(journal_entry_lines.credit), 0) as credit_total')
            ->get();

        $lines = [];
        $netByBranch = [];

        foreach ($rows as $row) {
            $netCents = $this->toCents($row->debit_total) - $this->toCents($row->credit_total);
            if ($netCents === 0) {
                continue;
            }

            $branchKey = $row->branch_id === null ? 'null' : (string) $row->branch_id;
            $netByBranch[$branchKey] = ($netByBranch[$branchKey] ?? 0) + $netCents;

            $lines[] = [
                'account_id' => $row->account_id,
                'branch_id' => $row->branch_id,
                'debit' => $netCents < 0 ? $this->fromCents(-$netCents) : '0.00',
                'credit' => $netCents > 0 ? $this->fromCents($netCents) : '0.00',
                'memo' => 'Close ' . $row->name,
            ];
        }

        $netIncomeCents = 0;
        foreach ($netByBranch as $branchKey => $branchNet) {
            $netIncomeCents -= $branchNet;
            $branchId = $branchKey === 'null' ? null : (int) $branchKey;

            $lines[] = [
                'account_id' => $periodClosing->retained_earnings_account_id,
                'branch_id' => $branchId,
                'debit' => $branchNet > 0 ? $this->fromCents($branchNet) : '0.00',
                'credit' => $branchNet < 0 ? $this->fromCents(-$branchNet) : '0.00',
                'memo' => 'Close net income to retained earnings',
            ];
        }

        $clearingAccountId = $this->clearing->resolveAccountIdForFiscalYear($periodClosing->fiscal_year_id);
        $lines = $this->clearing->inject($lines, $clearingAccountId);
        $this->clearing->assertBalancedPerBranch($lines);

        foreach ($lines as $line) {
            $entry->lines()->create([
                'account_id' => $line['account_id'],
                'branch_id' => $line['branch_id'] ?? null,
                'debit' => $line['debit'],
                'credit' => $line['credit'],
                'memo' => $line['memo'] ?? null,
            ]);
        }

        $periodClosing->update(['net_income' => $netIncomeCents / 100]);

        return $entry;
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
