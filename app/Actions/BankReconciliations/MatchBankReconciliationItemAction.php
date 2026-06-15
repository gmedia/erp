<?php

namespace App\Actions\BankReconciliations;

use App\Models\BankReconciliationItem;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MatchBankReconciliationItemAction
{
    public function execute(BankReconciliationItem $item, int $journalEntryLineId): BankReconciliationItem
    {
        return DB::transaction(function () use ($item, $journalEntryLineId): BankReconciliationItem {
            $line = JournalEntryLine::with('journalEntry')->findOrFail($journalEntryLineId);

            $this->validate($item, $line);

            $item->update([
                'journal_entry_line_id' => $line->id,
                'is_reconciled' => true,
            ]);

            $item->bankReconciliation->recalculateBalances();

            return $item->refresh();
        });
    }

    private function validate(BankReconciliationItem $item, JournalEntryLine $line): void
    {
        $reconciliation = $item->bankReconciliation;

        if ($line->account_id !== $reconciliation->account_id) {
            throw ValidationException::withMessages([
                'journal_entry_line_id' => ['The journal entry line does not belong to the reconciliation account.'],
            ]);
        }

        if ($line->journalEntry->status !== 'posted') {
            throw ValidationException::withMessages([
                'journal_entry_line_id' => ['The journal entry must be posted.'],
            ]);
        }

        $alreadyMatched = BankReconciliationItem::where('journal_entry_line_id', $line->id)
            ->where('id', '!=', $item->id)
            ->exists();

        if ($alreadyMatched) {
            throw ValidationException::withMessages([
                'journal_entry_line_id' => ['This journal entry line is already matched to another bank item.'],
            ]);
        }
    }
}
