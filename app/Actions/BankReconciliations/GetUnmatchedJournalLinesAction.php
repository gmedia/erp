<?php

namespace App\Actions\BankReconciliations;

use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use App\Models\JournalEntryLine;
use Illuminate\Support\Collection;

class GetUnmatchedJournalLinesAction
{
    /**
     * @return Collection<int, JournalEntryLine>
     */
    public function execute(BankReconciliation $bankReconciliation, ?string $search = null): Collection
    {
        $matchedLineIds = BankReconciliationItem::whereNotNull('journal_entry_line_id')
            ->pluck('journal_entry_line_id');

        $query = JournalEntryLine::query()
            ->where('journal_entry_lines.account_id', $bankReconciliation->account_id)
            ->whereHas('journalEntry', function ($q) use ($bankReconciliation): void {
                $q->where('status', 'posted')
                    ->whereBetween('entry_date', [
                        $bankReconciliation->period_start,
                        $bankReconciliation->period_end,
                    ]);
            })
            ->whereNotIn('journal_entry_lines.id', $matchedLineIds)
            ->with(['journalEntry:id,entry_date,reference,description,entry_number']);

        if ($search) {
            $query->where(function ($q) use ($search): void {
                $q->where('memo', 'like', "%{$search}%")
                    ->orWhereHas('journalEntry', function ($jq) use ($search): void {
                        $jq->where('reference', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
            });
        }

        return $query->limit(50)->get();
    }
}
