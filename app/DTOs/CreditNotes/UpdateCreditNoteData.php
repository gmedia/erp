<?php

namespace App\DTOs\CreditNotes;

readonly class UpdateCreditNoteData
{
    public function __construct(
        public ?string $credit_note_number = null,
        public ?int $customer_id = null,
        public ?int $customer_invoice_id = null,
        public ?int $branch_id = null,
        public ?int $fiscal_year_id = null,
        public ?string $credit_note_date = null,
        public ?string $reason = null,
        public ?string $subtotal = null,
        public ?string $tax_amount = null,
        public ?string $grand_total = null,
        public ?string $status = null,
        public ?string $notes = null,
        public ?int $journal_entry_id = null,
        public ?int $confirmed_by = null,
        public ?string $confirmed_at = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            credit_note_number: $data['credit_note_number'] ?? null,
            customer_id: $data['customer_id'] ?? null,
            customer_invoice_id: $data['customer_invoice_id'] ?? null,
            branch_id: $data['branch_id'] ?? null,
            fiscal_year_id: $data['fiscal_year_id'] ?? null,
            credit_note_date: $data['credit_note_date'] ?? null,
            reason: $data['reason'] ?? null,
            subtotal: isset($data['subtotal']) ? (string) $data['subtotal'] : null,
            tax_amount: isset($data['tax_amount']) ? (string) $data['tax_amount'] : null,
            grand_total: isset($data['grand_total']) ? (string) $data['grand_total'] : null,
            status: $data['status'] ?? null,
            notes: $data['notes'] ?? null,
            journal_entry_id: $data['journal_entry_id'] ?? null,
            confirmed_by: $data['confirmed_by'] ?? null,
            confirmed_at: $data['confirmed_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        $payload = [];

        if ($this->credit_note_number !== null) {
            $payload['credit_note_number'] = $this->credit_note_number;
        }
        if ($this->customer_id !== null) {
            $payload['customer_id'] = $this->customer_id;
        }
        if ($this->customer_invoice_id !== null) {
            $payload['customer_invoice_id'] = $this->customer_invoice_id;
        }
        if ($this->branch_id !== null) {
            $payload['branch_id'] = $this->branch_id;
        }
        if ($this->fiscal_year_id !== null) {
            $payload['fiscal_year_id'] = $this->fiscal_year_id;
        }
        if ($this->credit_note_date !== null) {
            $payload['credit_note_date'] = $this->credit_note_date;
        }
        if ($this->reason !== null) {
            $payload['reason'] = $this->reason;
        }
        if ($this->subtotal !== null) {
            $payload['subtotal'] = $this->subtotal;
        }
        if ($this->tax_amount !== null) {
            $payload['tax_amount'] = $this->tax_amount;
        }
        if ($this->grand_total !== null) {
            $payload['grand_total'] = $this->grand_total;
        }
        if ($this->status !== null) {
            $payload['status'] = $this->status;
        }
        if ($this->notes !== null) {
            $payload['notes'] = $this->notes;
        }
        if ($this->journal_entry_id !== null) {
            $payload['journal_entry_id'] = $this->journal_entry_id;
        }
        if ($this->confirmed_by !== null) {
            $payload['confirmed_by'] = $this->confirmed_by;
        }
        if ($this->confirmed_at !== null) {
            $payload['confirmed_at'] = $this->confirmed_at;
        }

        return $payload;
    }
}
