<?php

namespace App\Actions\AccountingPosting;

use App\Actions\Concerns\RoundsLineAmounts;
use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\CreditNote;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostCreditNoteJournalAction
{
    use RoundsLineAmounts;

    public const AR_CONTROL_ACCOUNT_CODE = '11200';

    public function __construct(
        private CreateJournalEntryAction $createJournalEntry,
        private ResolveControlAccountAction $resolveControlAccount,
    ) {}

    /**
     * Posts the journal for an applied credit note:
     * Dr sales returns/allowance accounts (per item), Cr Accounts Receivable 11200.
     * Mirrors the AR invoice convention, where AR is debited for the invoiced line totals.
     */
    public function execute(CreditNote $creditNote): ?JournalEntry
    {
        if ($creditNote->journal_entry_id !== null) {
            /** @var JournalEntry|null $existing */
            $existing = $creditNote->journalEntry()->first();

            return $existing;
        }

        return DB::transaction(function () use ($creditNote): JournalEntry {
            $creditNote->loadMissing('items');

            if ($creditNote->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Cannot post a credit note with no items.',
                ]);
            }

            $entryDate = $creditNote->credit_note_date->format('Y-m-d');
            $arAccount = $this->resolveControlAccount->execute(self::AR_CONTROL_ACCOUNT_CODE, $entryDate);

            // Departemen: pakai milik credit note; bila kosong, warisi dari invoice terkait.
            $departmentId = $creditNote->department_id
                ?? $creditNote->customerInvoice?->department_id;

            $debitGrouped = [];
            foreach ($creditNote->items as $item) {
                $accountId = (int) $item->account_id;
                $key = $accountId . '|' . ($departmentId ?? '');
                $debitGrouped[$key] = ($debitGrouped[$key] ?? [
                    'account_id' => $accountId,
                    'department_id' => $departmentId,
                    'amount' => 0.0,
                ]);
                $debitGrouped[$key]['amount'] += $this->roundLineAmount($item->line_total);
            }

            $lines = [];
            foreach ($debitGrouped as $group) {
                if ($group['amount'] <= 0) {
                    continue;
                }

                $lines[] = [
                    'account_id' => $group['account_id'],
                    'department_id' => $group['department_id'],
                    'debit' => $this->roundLineAmount($group['amount']),
                    'credit' => 0,
                    'memo' => "Credit Note {$creditNote->credit_note_number}",
                ];
            }

            $total = $this->sumRoundedLineAmounts(array_column($lines, 'debit'));

            $lines[] = [
                'account_id' => $arAccount->id,
                'debit' => 0,
                'credit' => $total,
                'memo' => "Credit Note {$creditNote->credit_note_number}",
            ];

            $journalEntry = $this->createJournalEntry->execute([
                'entry_date' => $entryDate,
                'reference' => $creditNote->credit_note_number,
                'description' => "Credit Note {$creditNote->credit_note_number}",
                'status' => 'posted',
                'journal_type' => 'system',
                'source_type' => CreditNote::class,
                'source_id' => $creditNote->id,
                'branch_id' => $creditNote->branch_id,
                'lines' => $lines,
            ]);

            $creditNote->forceFill([
                'journal_entry_id' => $journalEntry->id,
            ])->save();

            return $journalEntry;
        });
    }
}
