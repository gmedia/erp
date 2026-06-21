<?php

namespace App\Actions\JournalEntries;

use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Services\InterBranchClearingService;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateJournalEntryAction
{
    public function __construct(
        private InterBranchClearingService $clearing,
    ) {}

    /**
     * Create a journal entry with its lines.
     *
     * Optional keys (all backward compatible):
     * - status: 'draft'|'posted' (default 'draft'). When 'posted', posted_by/posted_at are
     *   filled and the entry is balance-verified before saving.
     * - journal_type: e.g. 'general', 'adjusting', 'closing', 'recurring', 'system'.
     * - branch_id: owning branch resolved by the caller; null for company-wide entries.
     * - source_type, source_id: morph reference to the originating document.
     */
    public function execute(array $data): JournalEntry
    {
        $attempts = 0;
        $maxAttempts = 3;

        $status = $data['status'] ?? 'draft';

        if ($status === 'posted') {
            $totalDebit = 0.0;
            $totalCredit = 0.0;
            foreach ($data['lines'] as $line) {
                $totalDebit += (float) ($line['debit'] ?? 0);
                $totalCredit += (float) ($line['credit'] ?? 0);
            }
            if (bccomp((string) $totalDebit, (string) $totalCredit, 2) !== 0) {
                throw ValidationException::withMessages([
                    'lines' => 'Journal entry is not balanced (debit must equal credit).',
                ]);
            }
        }

        while ($attempts < $maxAttempts) {
            try {
                return DB::transaction(function () use ($data, $status) {
                    $entryDate = $data['entry_date'];

                    $fiscalYear = FiscalYear::where('start_date', '<=', $entryDate)
                        ->where('end_date', '>=', $entryDate)
                        ->lockForUpdate()
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

                    // Generate Entry Number
                    // We use max() instead of count() to handle deletions correctly (gaps in sequence)
                    // Format: JV-{Year}-XXXXX
                    $lastEntry = JournalEntry::where('fiscal_year_id', $fiscalYear->id)
                        ->lockForUpdate()
                        ->orderBy('entry_number', 'desc')
                        ->first();

                    if ($lastEntry) {
                        $parts = explode('-', $lastEntry->entry_number);
                        $lastSequence = intval(end($parts));
                        $count = $lastSequence + 1;
                    } else {
                        $count = 1;
                    }

                    $entryNumber = 'JV-' . $fiscalYear->name . '-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);

                    $userId = Auth::id();

                    $attributes = [
                        'fiscal_year_id' => $fiscalYear->id,
                        'entry_number' => $entryNumber,
                        'entry_date' => $data['entry_date'],
                        'reference' => $data['reference'] ?? null,
                        'description' => $data['description'],
                        'status' => $status,
                        'journal_type' => $data['journal_type'] ?? 'general',
                        'branch_id' => $data['branch_id'] ?? null,
                        'source_type' => $data['source_type'] ?? null,
                        'source_id' => $data['source_id'] ?? null,
                        'created_by' => $userId,
                    ];

                    if ($status === 'posted') {
                        $attributes['posted_by'] = $userId;
                        $attributes['posted_at'] = now();
                    }

                    $journalEntry = JournalEntry::create($attributes);

                    $headerBranchId = $data['branch_id'] ?? null;
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

                    $clearingAccountId = $this->clearing->resolveAccountIdForFiscalYear($fiscalYear->id);
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

                    return $journalEntry;
                });
            } catch (QueryException $e) {
                // Check for unique duplicate error (SQLSTATE 23000)
                if ($e->getCode() == 23000 && str_contains($e->getMessage(), 'journal_entries_entry_number_unique')) {
                    $attempts++;
                    if ($attempts >= $maxAttempts) {
                        throw $e;
                    }

                    // Retry
                    continue;
                }
                throw $e;
            }
        }

        throw new Exception("Failed to create journal entry after {$maxAttempts} attempts.");
    }
}
