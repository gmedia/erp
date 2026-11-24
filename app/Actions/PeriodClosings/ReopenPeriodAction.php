<?php

namespace App\Actions\PeriodClosings;

use App\Actions\AccountingPosting\ReverseDocumentJournalAction;
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
            $originalId = $periodClosing->closing_journal_entry_id;

            if ($originalId) {
                $original = JournalEntry::query()->findOrFail($originalId);

                app(ReverseDocumentJournalAction::class)->execute(
                    $periodClosing,
                    'PC-' . $periodClosing->id . '-REOPEN',
                    'Penutup Periode',
                    $original->entry_date->toDateString(),
                    'closing',
                    $originalId,
                    isPeriodClosingEntry: true,
                );
            }

            $periodClosing->update([
                'status' => 'reopened',
                'reopened_by' => auth()->id(),
                'reopened_at' => now(),
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
