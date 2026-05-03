<?php

namespace App\DTOs\CustomerInvoices;

readonly class UpdateCustomerInvoiceData
{
    public function __construct(
        public ?string $invoice_number = null,
        public ?int $customer_id = null,
        public ?int $branch_id = null,
        public ?int $fiscal_year_id = null,
        public ?string $invoice_date = null,
        public ?string $due_date = null,
        public ?string $payment_terms = null,
        public ?string $currency = null,
        public ?string $subtotal = null,
        public ?string $tax_amount = null,
        public ?string $discount_amount = null,
        public ?string $grand_total = null,
        public ?string $amount_received = null,
        public ?string $credit_note_amount = null,
        public ?string $amount_due = null,
        public ?string $status = null,
        public ?string $notes = null,
        public ?int $journal_entry_id = null,
        public ?int $sent_by = null,
        public ?string $sent_at = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            invoice_number: $data['invoice_number'] ?? null,
            customer_id: $data['customer_id'] ?? null,
            branch_id: $data['branch_id'] ?? null,
            fiscal_year_id: $data['fiscal_year_id'] ?? null,
            invoice_date: $data['invoice_date'] ?? null,
            due_date: $data['due_date'] ?? null,
            payment_terms: $data['payment_terms'] ?? null,
            currency: $data['currency'] ?? null,
            subtotal: isset($data['subtotal']) ? (string) $data['subtotal'] : null,
            tax_amount: isset($data['tax_amount']) ? (string) $data['tax_amount'] : null,
            discount_amount: isset($data['discount_amount']) ? (string) $data['discount_amount'] : null,
            grand_total: isset($data['grand_total']) ? (string) $data['grand_total'] : null,
            amount_received: isset($data['amount_received']) ? (string) $data['amount_received'] : null,
            credit_note_amount: isset($data['credit_note_amount']) ? (string) $data['credit_note_amount'] : null,
            amount_due: isset($data['amount_due']) ? (string) $data['amount_due'] : null,
            status: $data['status'] ?? null,
            notes: $data['notes'] ?? null,
            journal_entry_id: $data['journal_entry_id'] ?? null,
            sent_by: $data['sent_by'] ?? null,
            sent_at: $data['sent_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $value): bool => $value !== null);
    }
}
