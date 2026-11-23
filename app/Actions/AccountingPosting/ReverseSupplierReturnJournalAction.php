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
                    'items' => 'Cannot reverse a supplier return journal with no lines.',
                ]);
            }

            $lines = $original->lines->map(fn ($line): array => [
                'account_id' => $line->account_id,
                'branch_id' => $line->branch_id,
                'debit' => (float) $line->credit,
                'credit' => (float) $line->debit,
                'memo' => 'Pembalik: ' . ($line->memo ?? $supplierReturn->return_number),
            ])->all();

            return $this->createJournalEntry->execute([
                'entry_date' => now()->format('Y-m-d'),
                'reference' => $supplierReturn->return_number,
                'description' => "Reversal Supplier Return {$supplierReturn->return_number}",
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
