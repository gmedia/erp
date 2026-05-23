<?php

namespace App\Actions\AccountingPosting;

use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\JournalEntry;
use App\Models\SupplierReturn;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostSupplierReturnJournalAction
{
    public const AP_CONTROL_ACCOUNT_CODE = '21100';

    public const INVENTORY_ACCOUNT_CODE = '11300';

    public function __construct(
        private CreateJournalEntryAction $createJournalEntry,
        private ResolveControlAccountAction $resolveControlAccount,
    ) {}

    public function execute(SupplierReturn $supplierReturn): ?JournalEntry
    {
        if ($supplierReturn->journal_entry_id !== null) {
            /** @var JournalEntry|null $existing */
            $existing = $supplierReturn->journalEntry()->first();

            return $existing;
        }

        if ($supplierReturn->status !== 'confirmed') {
            return null;
        }

        return DB::transaction(function () use ($supplierReturn): JournalEntry {
            $supplierReturn->loadMissing('items');

            if ($supplierReturn->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Cannot post a supplier return with no items.',
                ]);
            }

            $apAccount = $this->resolveControlAccount->execute(self::AP_CONTROL_ACCOUNT_CODE);
            $inventoryAccount = $this->resolveControlAccount->execute(self::INVENTORY_ACCOUNT_CODE);

            $total = 0.0;

            foreach ($supplierReturn->items as $item) {
                /** @var \App\Models\SupplierReturnItem $item */
                $total += (float) $item->quantity_returned * (float) $item->unit_price;
            }

            $total = round($total, 2);

            if ($total <= 0) {
                throw ValidationException::withMessages([
                    'items' => 'Cannot post a supplier return with zero or negative total value.',
                ]);
            }

            $memo = "Return {$supplierReturn->return_number}";

            $lines = [
                [
                    'account_id' => $apAccount->id,
                    'debit' => $total,
                    'credit' => 0,
                    'memo' => $memo,
                ],
                [
                    'account_id' => $inventoryAccount->id,
                    'debit' => 0,
                    'credit' => $total,
                    'memo' => $memo,
                ],
            ];

            $journalEntry = $this->createJournalEntry->execute([
                'entry_date' => $supplierReturn->return_date->format('Y-m-d'),
                'reference' => $supplierReturn->return_number,
                'description' => "Supplier Return {$supplierReturn->return_number}",
                'status' => 'posted',
                'journal_type' => 'system',
                'source_type' => SupplierReturn::class,
                'source_id' => $supplierReturn->id,
                'lines' => $lines,
            ]);

            $supplierReturn->forceFill([
                'journal_entry_id' => $journalEntry->id,
            ])->save();

            return $journalEntry;
        });
    }
}
