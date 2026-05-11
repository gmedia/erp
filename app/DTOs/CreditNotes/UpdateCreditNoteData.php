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
        return array_filter(get_object_vars($this), static fn (mixed $value): bool => $value !== null);
    }
}
