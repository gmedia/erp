<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Model;

/**
 * Mengunci item, alokasi, dan nilai dokumen yang sudah terposting (Issue #375).
 *
 * Dokumen terposting = punya journal_entry_id dan bukan draft. Nilai yang
 * berbeda dari tersimpan ditolak 422; payload identik tetap diterima.
 * Perubahan status (kirim ulang, batal/void) ditangani validasi transisi.
 */
trait LocksPostedDocumentValues
{
    /** @return array<int, string> field nilai yang dibandingkan dengan data tersimpan */
    protected function lockedValueFields(): array
    {
        return ['total_amount', 'subtotal', 'grand_total', 'amount_paid', 'amount_received', 'amount_due'];
    }

    /** @return array<int, string> field koleksi (items/allocations) yang tidak boleh diubah */
    protected function lockedCollectionFields(): array
    {
        return ['items', 'allocations'];
    }

    protected function validatePostedDocumentLock(Validator $validator, ?Model $document): void
    {
        if ($document === null || $document->getAttribute('journal_entry_id') === null) {
            return;
        }

        if ($document->getAttribute('status') === 'draft') {
            return;
        }

        foreach ($this->lockedValueFields() as $field) {
            if ($this->has($field) && ! $this->valueMatches($document, $field)) {
                $this->addPostedLockError($validator, $field);
            }
        }

        foreach ($this->lockedCollectionFields() as $field) {
            $incoming = $this->input($field);

            if (is_array($incoming) && $incoming !== [] && ! $this->collectionMatches($document, $field, $incoming)) {
                $this->addPostedLockError($validator, $field);
            }
        }
    }

    private function valueMatches(Model $document, string $field): bool
    {
        $incoming = $this->input($field);

        if (! is_numeric($incoming)) {
            return false;
        }

        return round((float) $incoming, 2) === round((float) $document->getAttribute($field), 2);
    }

    /**
     * Koleksi sama bila jumlah baris dan total nilainya identik.
     *
     * @param  array<int, mixed>  $incoming
     */
    private function collectionMatches(Model $document, string $field, array $incoming): bool
    {
        $stored = $document->loadMissing($field)->getRelation($field);

        if ($stored->count() !== count($incoming)) {
            return false;
        }

        $incomingTotal = array_sum(array_map(fn (array $row): float => $this->rowAmount($row), $incoming));
        $storedTotal = $stored->sum(fn (Model $row): float => $this->rowAmount($row->getAttributes()));

        return round($incomingTotal, 2) === round((float) $storedTotal, 2);
    }

    /**
     * Nilai satu baris: alokasi memakai allocated_amount, item memakai quantity x unit_price.
     *
     * @param  array<string, mixed>  $row
     */
    private function rowAmount(array $row): float
    {
        if (isset($row['allocated_amount'])) {
            return (float) $row['allocated_amount'];
        }

        return (float) ($row['quantity'] ?? 0) * (float) ($row['unit_price'] ?? 0);
    }

    private function addPostedLockError(Validator $validator, string $field): void
    {
        $validator->errors()->add($field, __('posted_document.values_locked'));
    }
}
