<?php

namespace App\Actions\PeriodClosings;

use App\Models\PeriodClosing;
use Illuminate\Validation\ValidationException;

class ReopenPeriodAction
{
    public function execute(PeriodClosing $periodClosing): PeriodClosing
    {
        if (! $periodClosing->isClosed()) {
            throw ValidationException::withMessages(['status' => 'Only closed periods can be reopened.']);
        }

        $periodClosing->update(['status' => 'reopened', 'reopened_by' => auth()->id(), 'reopened_at' => now()]);

        return $periodClosing->refresh()->load(['fiscalYear', 'closingJournalEntry', 'retainedEarningsAccount', 'closedBy', 'reopenedBy', 'creator']);
    }
}
