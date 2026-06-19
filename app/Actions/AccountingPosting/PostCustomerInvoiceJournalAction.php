<?php

namespace App\Actions\AccountingPosting;

use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\CustomerInvoice;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostCustomerInvoiceJournalAction
{
    public const AR_CONTROL_ACCOUNT_CODE = '11200';

    public function __construct(
        private CreateJournalEntryAction $createJournalEntry,
        private ResolveControlAccountAction $resolveControlAccount,
    ) {}

    public function execute(CustomerInvoice $invoice): ?JournalEntry
    {
        if ($invoice->journal_entry_id !== null) {
            /** @var JournalEntry|null $existing */
            $existing = $invoice->journalEntry()->first();

            return $existing;
        }

        if ($invoice->status !== 'sent') {
            return null;
        }

        return DB::transaction(function () use ($invoice): JournalEntry {
            $invoice->loadMissing('items');

            if ($invoice->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Cannot post a customer invoice with no items.',
                ]);
            }

            $arAccount = $this->resolveControlAccount->execute(self::AR_CONTROL_ACCOUNT_CODE);

            $creditTotal = 0.0;
            $creditLines = [];
            $revenueGrouped = [];

            foreach ($invoice->items as $item) {
                $accountId = (int) $item->account_id;
                $revenueGrouped[$accountId] = ($revenueGrouped[$accountId] ?? 0.0) + (float) $item->line_total;
            }

            foreach ($revenueGrouped as $accountId => $amount) {
                if ($amount <= 0) {
                    continue;
                }

                $creditLines[] = [
                    'account_id' => $accountId,
                    'debit' => 0,
                    'credit' => round($amount, 2),
                    'memo' => "Invoice {$invoice->invoice_number}",
                ];
                $creditTotal += $amount;
            }

            $creditTotal = round($creditTotal, 2);

            $debitLine = [
                'account_id' => $arAccount->id,
                'debit' => $creditTotal,
                'credit' => 0,
                'memo' => "Invoice {$invoice->invoice_number}",
            ];

            $journalEntry = $this->createJournalEntry->execute([
                'entry_date' => $invoice->invoice_date->format('Y-m-d'),
                'reference' => $invoice->invoice_number,
                'description' => "Customer Invoice {$invoice->invoice_number}",
                'status' => 'posted',
                'journal_type' => 'system',
                'source_type' => CustomerInvoice::class,
                'source_id' => $invoice->id,
                'branch_id' => $invoice->branch_id,
                'lines' => array_merge([$debitLine], $creditLines),
            ]);

            $invoice->forceFill([
                'journal_entry_id' => $journalEntry->id,
            ])->save();

            return $journalEntry;
        });
    }
}
