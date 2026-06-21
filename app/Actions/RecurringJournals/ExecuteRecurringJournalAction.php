<?php

namespace App\Actions\RecurringJournals;

use App\Models\JournalEntry;
use App\Models\RecurringJournal;
use App\Services\InterBranchClearingService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExecuteRecurringJournalAction
{
    public function __construct(
        private InterBranchClearingService $clearing,
    ) {}

    public function execute(RecurringJournal $recurringJournal): JournalEntry
    {
        return DB::transaction(function () use ($recurringJournal): JournalEntry {
            $recurringJournal->load(['lines.account', 'fiscalYear']);

            if (! $recurringJournal->is_active) {
                throw ValidationException::withMessages(['recurring_journal' => 'Recurring journal is inactive.']);
            }

            if (! $recurringJournal->isBalanced()) {
                throw ValidationException::withMessages(['lines' => 'Recurring journal lines are not balanced.']);
            }

            $entry = JournalEntry::create([
                'fiscal_year_id' => $recurringJournal->fiscal_year_id,
                'entry_number' => $this->nextEntryNumber(),
                'entry_date' => now()->toDateString(),
                'reference' => 'Recurring Journal #' . $recurringJournal->id,
                'description' => $recurringJournal->name,
                'status' => $recurringJournal->auto_post ? 'posted' : 'draft',
                'journal_type' => 'recurring',
                'source_type' => RecurringJournal::class,
                'source_id' => $recurringJournal->id,
                'created_by' => auth()->id(),
                'posted_by' => $recurringJournal->auto_post ? auth()->id() : null,
                'posted_at' => $recurringJournal->auto_post ? now() : null,
            ]);

            $resolvedLines = $recurringJournal->lines->map(fn ($line): array => [
                'account_id' => $line->account_id,
                'branch_id' => $line->branch_id ?? null,
                'debit' => $line->debit,
                'credit' => $line->credit,
                'memo' => $line->memo,
            ])->all();

            $clearingAccountId = $this->clearing->resolveAccountIdForFiscalYear($recurringJournal->fiscal_year_id);
            $resolvedLines = $this->clearing->inject($resolvedLines, $clearingAccountId);
            $this->clearing->assertBalancedPerBranch($resolvedLines);

            foreach ($resolvedLines as $line) {
                $entry->lines()->create([
                    'account_id' => $line['account_id'],
                    'branch_id' => $line['branch_id'] ?? null,
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'memo' => $line['memo'] ?? null,
                ]);
            }

            $recurringJournal->update([
                'last_run_date' => now()->toDateString(),
                'next_run_date' => $this->nextRunDate($recurringJournal),
            ]);

            return $entry->load(['lines.account', 'fiscalYear', 'createdBy', 'postedBy']);
        });
    }

    private function nextEntryNumber(): string
    {
        return 'RJ-' . now()->format('YmdHis') . '-' . str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);
    }

    private function nextRunDate(RecurringJournal $recurringJournal): CarbonInterface
    {
        $date = $recurringJournal->next_run_date->copy();

        return match ($recurringJournal->frequency) {
            'daily' => $date->addDay(),
            'weekly' => $date->addWeek(),
            'quarterly' => $date->addQuarter(),
            'annual' => $date->addYear(),
            default => $date->addMonth(),
        };
    }
}
