<?php

namespace App\Http\Controllers\Concerns;

use App\Actions\AccountingPosting\ReverseDocumentJournalAction;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Issue #375: pembatalan dokumen terposting lewat jurnal pembalik (bukan DELETE).
 *
 * Dokumen dikunci, lalu ditolak (422) bila masih punya turunan aktif yang harus
 * lebih dulu dilepas. Bila lolos, jurnal dibalik dan status menjadi batal dalam
 * satu transaksi. Pembatalan kedua ditolak karena status sudah final.
 */
trait CancelsPostedDocuments
{
    /**
     * @param  array<string, mixed>  $attributes  Atribut PUT yang sudah divalidasi (mis. status, notes)
     * @param  array<int, array{0: Closure, 1: string}>  $blockers  [kondisi masih aktif, pesan 422]
     */
    protected function cancelPostedDocument(
        Model $document,
        array $attributes,
        array $blockers,
        string $reference,
        string $label,
        ReverseDocumentJournalAction $reverseJournal,
    ): void {
        DB::transaction(function () use ($document, $attributes, $blockers, $reference, $label, $reverseJournal): void {
            $locked = $document::query()->whereKey($document->getKey())->lockForUpdate()->firstOrFail();

            if (in_array($locked->getAttribute('status'), ['cancelled', 'void'], true)) {
                throw ValidationException::withMessages([
                    'status' => [__('Document is already cancelled.')],
                ]);
            }

            foreach ($blockers as [$isActive, $message]) {
                if ($isActive($locked)) {
                    throw ValidationException::withMessages(['status' => [$message]]);
                }
            }

            $locked->update($attributes);

            $reverseJournal->execute($locked, $reference, $label);

            $document->refresh();
        });
    }
}
