<?php

namespace App\Actions\AccountingPosting;

use App\Actions\Concerns\RoundsLineAmounts;
use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\CustomerInvoice;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostCustomerInvoiceJournalAction
{
    use RoundsLineAmounts;

    public const AR_CONTROL_ACCOUNT_CODE = '11200';

    /** @var list<string> */
    public const POSTABLE_STATUSES = ['sent', 'partially_paid', 'paid', 'overdue'];

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

        // Dokumen yang sudah dikirim lalu berubah menjadi partially_paid/paid/overdue
        // tetap harus punya jurnal AR; draft/cancelled/void tidak diposting.
        if (! in_array($invoice->status, self::POSTABLE_STATUSES, true)) {
            return null;
        }

        return DB::transaction(function () use ($invoice): JournalEntry {
            $invoice->loadMissing('items');

            if ($invoice->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Cannot post a customer invoice with no items.',
                ]);
            }

            $entryDate = $invoice->invoice_date->format('Y-m-d');
            $arAccount = $this->resolveControlAccount->execute(self::AR_CONTROL_ACCOUNT_CODE, $entryDate);

            $creditLines = [];
            $revenueGrouped = [];

            // Departemen dokumen diwariskan ke baris pendapatan saja (akun revenue); AR tetap null.
            foreach ($invoice->items as $item) {
                $accountId = (int) $item->account_id;
                $key = $accountId . '|' . ($invoice->department_id ?? '');
                $revenueGrouped[$key] = ($revenueGrouped[$key] ?? [
                    'account_id' => $accountId,
                    'department_id' => $invoice->department_id,
                    'amount' => 0.0,
                ]);
                $revenueGrouped[$key]['amount'] += $this->roundLineAmount($item->line_total);
            }

            foreach ($revenueGrouped as $group) {
                if ($group['amount'] <= 0) {
                    continue;
                }

                $creditLines[] = [
                    'account_id' => $group['account_id'],
                    'department_id' => $group['department_id'],
                    'debit' => 0,
                    'credit' => $this->roundLineAmount($group['amount']),
                    'memo' => "Invoice {$invoice->invoice_number}",
                ];
            }

            $creditTotal = $this->sumRoundedLineAmounts(array_column($creditLines, 'credit'));

            $debitLine = [
                'account_id' => $arAccount->id,
                'debit' => $creditTotal,
                'credit' => 0,
                'memo' => "Invoice {$invoice->invoice_number}",
            ];

            $journalEntry = $this->createJournalEntry->execute([
                'entry_date' => $entryDate,
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
