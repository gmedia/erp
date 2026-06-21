<?php

namespace App\Actions\PeriodClosings;

use App\Models\JournalEntry;
use App\Models\PeriodClosing;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReopenPeriodAction
{
    public function execute(PeriodClosing $periodClosing): PeriodClosing
    {
        if (! $periodClosing->isClosed()) {
            throw ValidationException::withMessages([
                'status' => 'Only closed periods can be reopened.',
            ]);
        }

        return DB::transaction(function () use ($periodClosing): PeriodClosing {
            if ($periodClosing->closing_journal_entry_id) {
                JournalEntry::where('id', $periodClosing->closing_journal_entry_id)->delete();
            }

            $periodClosing->update([
                'status' => 'reopened',
                'reopened_by' => auth()->id(),
                'reopened_at' => now(),
                'closing_journal_entry_id' => null,
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
}
