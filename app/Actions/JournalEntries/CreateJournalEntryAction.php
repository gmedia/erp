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
        return DB::transaction(function () use ($data) {
            $entryDate = $data['entry_date'];
            
            $fiscalYear = FiscalYear::where('start_date', '<=', $entryDate)
                ->where('end_date', '>=', $entryDate)
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

            // Generate Entry Number (Simple implementation, can be improved)
            $count = JournalEntry::where('fiscal_year_id', $fiscalYear->id)->count() + 1;
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
    }
}
