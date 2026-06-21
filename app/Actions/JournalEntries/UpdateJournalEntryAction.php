<?php

namespace App\Actions\JournalEntries;

use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Services\InterBranchClearingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateJournalEntryAction
{
    public function __construct(
        private InterBranchClearingService $clearing,
    ) {}

    public function execute(JournalEntry $journalEntry, array $data): JournalEntry
    {
        if ($journalEntry->status === 'posted' || $journalEntry->status === 'void') {
            throw ValidationException::withMessages([
                'status' => 'Cannot update posted or voided journal entry.',
            ]);
        }

        return DB::transaction(function () use ($journalEntry, $data) {
            $entryDate = $data['entry_date'];

            // Check if date changed and if it affects Fiscal Year
            if ($journalEntry->entry_date->format('Y-m-d') !== $entryDate) {
                $fiscalYear = FiscalYear::where('start_date', '<=', $entryDate)
                    ->where('end_date', '>=', $entryDate)
                    ->first();

                if (! $fiscalYear) {
                    throw ValidationException::withMessages([
                        'entry_date' => 'No fiscal year found for this date.',
                    ]);
                }

                if (! $fiscalYear->isOpen()) {
                    throw ValidationException::withMessages([
                        'entry_date' => 'Fiscal year is closed or locked.',
                    ]);
                }

                // If fiscal year changed, verify if we should change entry number?
                // For simplicity, we keep entry number or basic logic.
                // But ideally, entry number might need to change or be kept.
                // Let's update fiscal_year_id
                $journalEntry->fiscal_year_id = $fiscalYear->id;
            }

            $journalEntry->update([
                'entry_date' => $data['entry_date'],
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'],
            ]);

            $journalEntry->lines()->delete();

            $headerBranchId = $journalEntry->branch_id;
            $resolvedLines = array_map(
                fn (array $line): array => [
                    'account_id' => $line['account_id'],
                    'branch_id' => $line['branch_id'] ?? $headerBranchId,
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'memo' => $line['memo'] ?? null,
                ],
                $data['lines'],
            );

            $clearingAccountId = $this->clearing->resolveAccountIdForFiscalYear($journalEntry->fiscal_year_id);
            $resolvedLines = $this->clearing->inject($resolvedLines, $clearingAccountId);
            $this->clearing->assertBalancedPerBranch($resolvedLines);

            foreach ($resolvedLines as $lineData) {
                $journalEntry->lines()->create([
                    'account_id' => $lineData['account_id'],
                    'branch_id' => $lineData['branch_id'] ?? null,
                    'debit' => $lineData['debit'],
                    'credit' => $lineData['credit'],
                    'memo' => $lineData['memo'] ?? null,
                ]);
            }

            return $journalEntry->refresh();
        });
    }
}
