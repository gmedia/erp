<?php

namespace App\Http\Requests\Concerns;

use DateTimeInterface;
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

    /**
     * Header yang ikut posting (pihak, cabang, tahun fiskal, tanggal, mata uang, akun).
     * Nilai sama tetap diterima; yang berbeda ditolak 422.
     *
     * @return array<int, string>
     */
    protected function lockedHeaderFields(): array
    {
        return [
            'customer_id', 'supplier_id', 'branch_id', 'department_id', 'fiscal_year_id', 'currency',
            'customer_invoice_id', 'goods_receipt_id', 'purchase_order_id', 'payment_method',
            'invoice_date', 'bill_date', 'receipt_date', 'payment_date', 'supplier_invoice_date',
            'credit_note_date', 'due_date', 'bank_account_id', 'account_id',
        ];
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

        foreach ($this->lockedHeaderFields() as $field) {
            if ($this->has($field) && ! $this->headerMatches($document, $field)) {
                $this->addPostedLockError($validator, $field);
            }
        }

        foreach ($this->lockedCollectionFields() as $field) {
            if (! $this->has($field)) {
                continue;
            }

            $incoming = $this->input($field);

            if (! is_array($incoming) || ! $this->collectionMatches($document, $field, $incoming)) {
                $this->addPostedLockError($validator, $field);
            }
        }
    }

    private function headerMatches(Model $document, string $field): bool
    {
        $stored = $document->getAttribute($field);
        $incoming = $this->input($field);

        if ($stored instanceof DateTimeInterface) {
            $stored = $stored->format('Y-m-d');
        }

        if (is_string($incoming) && preg_match('/^\d{4}-\d{2}-\d{2}/', $incoming) === 1) {
            $incoming = substr($incoming, 0, 10);
        }

        return $this->scalarEquals($incoming, $stored);
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
     * Koleksi sama bila jumlah baris sama dan tiap baris (per posisi) identik
     * pada field yang memengaruhi nilai, akun, dan tujuan alokasi.
     *
     * @param  array<int, mixed>  $incoming
     */
    private function collectionMatches(Model $document, string $field, array $incoming): bool
    {
        $stored = $document->loadMissing($field)->getRelation($field)->values();

        if ($stored->count() !== count($incoming) || $incoming === []) {
            return false;
        }

        foreach ($incoming as $index => $row) {
            if (! is_array($row) || ! $this->rowMatches($stored[$index], $row)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function rowMatches(Model $storedRow, array $row): bool
    {
        foreach ($this->lockedRowFields() as $key) {
            if (! array_key_exists($key, $row)) {
                continue;
            }

            if (! $this->scalarEquals($row[$key], $storedRow->getAttribute($key))) {
                return false;
            }
        }

        return true;
    }

    /** Field baris yang dikunci: nilai, akun, produk, pajak, diskon, dan tujuan alokasi. */
    private function lockedRowFields(): array
    {
        return [
            'account_id', 'product_id', 'unit_id', 'quantity', 'unit_price',
            'discount_percent', 'tax_percent', 'line_total', 'allocated_amount',
            'customer_invoice_id', 'supplier_bill_id', 'discount_given', 'discount_taken',
        ];
    }

    private function scalarEquals(mixed $incoming, mixed $stored): bool
    {
        if (is_numeric($incoming) && is_numeric($stored)) {
            return round((float) $incoming, 4) === round((float) $stored, 4);
        }

        return (string) ($incoming ?? '') === (string) ($stored ?? '');
    }

    private function addPostedLockError(Validator $validator, string $field): void
    {
        $validator->errors()->add($field, __('posted_document.values_locked'));
    }
}
