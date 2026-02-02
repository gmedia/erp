<?php

namespace App\Actions\JournalEntries;

use App\Models\FiscalYear;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateJournalEntryAction
{
    public function execute(array $data): JournalEntry
    {
        $attempts = 0;
        $maxAttempts = 3;

        while ($attempts < $maxAttempts) {
            try {
                return DB::transaction(function () use ($data) {
                    $entryDate = $data['entry_date'];
                    
                    $fiscalYear = FiscalYear::where('start_date', '<=', $entryDate)
                        ->where('end_date', '>=', $entryDate)
                        ->lockForUpdate()
                        ->first();

                    if (!$fiscalYear) {
                        throw ValidationException::withMessages([
                            'entry_date' => 'No fiscal year found for this date.',
                        ]);
                    }

                    if (!$fiscalYear->isOpen()) {
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
                    
                    $entryNumber = 'JV-' . $fiscalYear->name . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);

                    $journalEntry = JournalEntry::create([
                        'fiscal_year_id' => $fiscalYear->id,
                        'entry_number' => $entryNumber,
                        'entry_date' => $data['entry_date'],
                        'reference' => $data['reference'] ?? null,
                        'description' => $data['description'],
                        'status' => 'draft', // Default to draft
                        'created_by' => auth()->id(),
                    ]);

                    foreach ($data['lines'] as $lineData) {
                        $journalEntry->lines()->create([
                            'account_id' => $lineData['account_id'],
                            'debit' => $lineData['debit'],
                            'credit' => $lineData['credit'],
                            'memo' => $lineData['memo'] ?? null,
                        ]);
                    }

                    return $journalEntry;
                });
            } catch (\Illuminate\Database\QueryException $e) {
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
        
        throw new \Exception("Failed to create journal entry after {$maxAttempts} attempts.");
    }
}
