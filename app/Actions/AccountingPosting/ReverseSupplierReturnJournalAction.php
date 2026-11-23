<?php

namespace App\Actions\AccountingPosting;

use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\JournalEntry;
use App\Models\SupplierReturn;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Membalik jurnal retur supplier yang sudah diposting (Issue #362).
 *
 * Baris pembalik diambil dari baris jurnal asli (akun dan nilai dibalik), bukan dari
 * akun tetap, karena akun GR/bill dapat berubah (#357). Tanggal = tanggal pembatalan.
 * Periode tertutup ditolak oleh CreateJournalEntryAction (422).
 */
class ReverseSupplierReturnJournalAction
{
    public function __construct(private CreateJournalEntryAction $createJournalEntry) {}

    public function execute(SupplierReturn $supplierReturn): ?JournalEntry
    {
        if ($supplierReturn->journal_entry_id === null) {
            return null;
        }

        return DB::transaction(function () use ($supplierReturn): JournalEntry {
            /** @var JournalEntry $original */
            $original = JournalEntry::query()
                ->with('lines')
                ->lockForUpdate()
                ->findOrFail($supplierReturn->journal_entry_id);

            if ($original->lines->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => [__('supplier_return.reversal_no_lines')],
                ]);
            }

            $lines = $original->lines->map(fn ($line): array => [
                'account_id' => $line->account_id,
                'branch_id' => $line->branch_id,
                'debit' => (float) $line->credit,
                'credit' => (float) $line->debit,
                'memo' => __('supplier_return.reversal_memo', [
                    'memo' => $line->memo ?? $supplierReturn->return_number,
                ]),
            ])->all();

            return $this->createJournalEntry->execute([
                'entry_date' => now()->format('Y-m-d'),
                'reference' => $supplierReturn->return_number,
                'description' => __('supplier_return.reversal_description', [
                    'number' => $supplierReturn->return_number,
                ]),
                'status' => 'posted',
                'journal_type' => 'system',
                'source_type' => SupplierReturn::class,
                'source_id' => $supplierReturn->id,
                'branch_id' => $original->branch_id,
                'lines' => $lines,
            ]);
        });
    }
}
