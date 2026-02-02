<?php

namespace App\Actions\JournalEntries;

use App\Models\FiscalYear;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateJournalEntryAction
{
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

            // Sync Lines
            // 1. Get existing line IDs
            $existingLines = $journalEntry->lines->keyBy('id');
            $newLinesData = collect($data['lines']);

            // 2. Identify IDs to keep/update
            $linesToUpdate = $newLinesData->whereNotNull('id'); // Assuming frontend sends ID for existing lines? 
            // Wait, standard FE might send all lines. If ID exists, update. If not, create.
            // But we don't have ID in StoreRequest/UpdateRequests rules for lines. 
            // We should assume complete replacement or smart diff.
            // Easier: Delete all and recreate? Or diff by content?
            // "Sync" by ID is safest if FE sends it.
            
            // Let's assume we delete all and recreate for simplicity in this iteration, 
            // unless we want to preserve IDs.
            // Preserving IDs is better. 
            // Let's update lines.* request rule to include ID if possible
            
            // Re-visiting UpdateJournalEntryRequest... 
            // I didn't verify if I added ID there. I didn't. 
            // So for now, I will DELETE ALL and RECREATE lines. 
            // This is acceptable for simple Journal Entry CRUD where lines are child entities.
            
            $journalEntry->lines()->delete();

            foreach ($data['lines'] as $lineData) {
                $journalEntry->lines()->create([
                    'account_id' => $lineData['account_id'],
                    'debit' => $lineData['debit'],
                    'credit' => $lineData['credit'],
                    'memo' => $lineData['memo'] ?? null,
                ]);
            }
            
            return $journalEntry->refresh();
        });
    }
}
