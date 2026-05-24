<?php

namespace App\Actions\BankReconciliations;

use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use App\Models\JournalEntryLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AutoMatchBankReconciliationAction
{
    /**
     * @return array{matched: int, unmatched: int}
     */
    public function execute(BankReconciliation $bankReconciliation): array
    {
        return DB::transaction(function () use ($bankReconciliation): array {
            $unmatchedItems = $bankReconciliation->items()
                ->where('is_reconciled', false)
                ->whereNull('journal_entry_line_id')
                ->get();

            if ($unmatchedItems->isEmpty()) {
                return ['matched' => 0, 'unmatched' => 0];
            }

            $candidates = $this->loadCandidates($bankReconciliation);
            $matched = 0;

            /** @var BankReconciliationItem $item */
            foreach ($unmatchedItems as $item) {
                $matchedLine = $this->findMatch($item, $candidates, $bankReconciliation);

                if ($matchedLine) {
                    $item->update([
                        'journal_entry_line_id' => $matchedLine->id,
                        'is_reconciled' => true,
                    ]);
                    $candidates = $candidates->reject(fn (JournalEntryLine $c): bool => $c->id === $matchedLine->id);
                    $matched++;
                }
            }

            return [
                'matched' => $matched,
                'unmatched' => $unmatchedItems->count() - $matched,
            ];
        });
    }

    /**
     * @return Collection<int, JournalEntryLine>
     */
    private function loadCandidates(BankReconciliation $bankReconciliation): Collection
    {
        $matchedLineIds = BankReconciliationItem::whereNotNull('journal_entry_line_id')
            ->pluck('journal_entry_line_id');

        return JournalEntryLine::query()
            ->where('journal_entry_lines.account_id', $bankReconciliation->account_id)
            ->whereHas('journalEntry', function ($query) use ($bankReconciliation): void {
                $query->where('status', 'posted')
                    ->whereBetween('entry_date', [
                        $bankReconciliation->period_start,
                        $bankReconciliation->period_end,
                    ]);
            })
            ->whereNotIn('journal_entry_lines.id', $matchedLineIds)
            ->with(['journalEntry:id,entry_date,reference,description'])
            ->get();
    }

    private function findMatch(
        BankReconciliationItem $item,
        Collection $candidates,
        BankReconciliation $bankReconciliation,
    ): ?JournalEntryLine {
        // Priority 1: exact reference + amount
        if ($item->reference) {
            $match = $candidates->first(function (JournalEntryLine $line) use ($item): bool {
                return $line->journalEntry->reference === $item->reference
                    && $this->amountMatches($item, $line);
            });

            if ($match) {
                return $match;
            }
        }

        // Priority 2: amount + date within 3 days
        $match = $candidates->first(function (JournalEntryLine $line) use ($item): bool {
            if (! $this->amountMatches($item, $line)) {
                return false;
            }

            $entryDate = $line->journalEntry->entry_date;

            return abs($item->transaction_date->diffInDays($entryDate)) <= 3;
        });

        if ($match) {
            return $match;
        }

        // Priority 3: amount only
        return $candidates->first(fn (JournalEntryLine $line): bool => $this->amountMatches($item, $line));
    }

    private function amountMatches(BankReconciliationItem $item, JournalEntryLine $line): bool
    {
        $itemDebit = (string) $item->debit;
        $itemCredit = (string) $item->credit;
        $lineDebit = (string) $line->debit;
        $lineCredit = (string) $line->credit;

        $hasDebit = bccomp($itemDebit, '0', 2) > 0;
        $hasCredit = bccomp($itemCredit, '0', 2) > 0;

        if ($hasDebit && bccomp($lineDebit, '0', 2) > 0) {
            return bccomp($itemDebit, $lineDebit, 2) === 0;
        }

        if ($hasCredit && bccomp($lineCredit, '0', 2) > 0) {
            return bccomp($itemCredit, $lineCredit, 2) === 0;
        }

        return false;
    }
}
