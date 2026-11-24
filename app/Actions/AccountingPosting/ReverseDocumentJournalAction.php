<?php

namespace App\Actions\AccountingPosting;

use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Mekanisme pembalik jurnal bersama untuk dokumen terposting (Issue #375).
 *
 * Baris pembalik diambil dari baris jurnal ASLI (akun dan nilai dibalik), bukan dari akun
 * tetap, karena akun dapat berubah sejak posting (#357). Tanggal = tanggal pembatalan.
 * Jurnal asli tidak diubah. Periode tertutup ditolak oleh CreateJournalEntryAction (422).
 * Idempotensi dijaga pemanggil: dokumen dikunci dan guard status menolak batal kedua (422).
 */
class ReverseDocumentJournalAction
{
    public function __construct(
        private CreateJournalEntryAction $createJournalEntry,
    ) {}

    /**
     * @param  string|null  $entryDate  Tanggal pembalik; null = hari ini (pembatalan dokumen).
     * @param  string|null  $journalType  Tipe jurnal pembalik; null = 'system'.
     */
    public function execute(
        Model $document,
        string $reference,
        string $label,
        ?string $entryDate = null,
        ?string $journalType = null,
        ?int $journalEntryId = null,
    ): ?JournalEntry {
        $journalEntryId ??= $document->getAttribute('journal_entry_id');

        if ($journalEntryId === null) {
            return null;
        }

        return DB::transaction(function () use (
            $reference,
            $label,
            $journalEntryId,
            $document,
            $entryDate,
            $journalType,
        ): JournalEntry {
            /** @var JournalEntry $original */
            $original = JournalEntry::query()
                ->with('lines')
                ->lockForUpdate()
                ->findOrFail($journalEntryId);

            if ($original->lines->isEmpty()) {
                throw ValidationException::withMessages([
                    'journal' => ["Jurnal {$label} tanpa baris tidak bisa dibalik."],
                ]);
            }

            $lines = $original->lines->map(fn ($line): array => [
                'account_id' => $line->account_id,
                'branch_id' => $line->branch_id,
                'debit' => (float) $line->credit,
                'credit' => (float) $line->debit,
                'memo' => 'Pembalik: ' . ($line->memo ?? $reference),
            ])->all();

            return $this->createJournalEntry->execute([
                'entry_date' => $entryDate ?? now()->format('Y-m-d'),
                'reference' => $reference,
                'description' => "Reversal {$label} {$reference}",
                'status' => 'posted',
                'journal_type' => $journalType ?? 'system',
                'source_type' => $document->getMorphClass(),
                'source_id' => $document->getKey(),
                'branch_id' => $original->branch_id,
                'lines' => $lines,
            ]);
        });
    }
}
