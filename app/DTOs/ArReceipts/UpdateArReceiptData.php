<?php

namespace App\DTOs\ArReceipts;

readonly class UpdateArReceiptData
{
    public function __construct(
        public ?string $receipt_number = null,
        public ?int $customer_id = null,
        public ?int $branch_id = null,
        public ?int $fiscal_year_id = null,
        public ?string $receipt_date = null,
        public ?string $payment_method = null,
        public ?int $bank_account_id = null,
        public ?string $currency = null,
        public ?string $total_amount = null,
        public ?string $total_allocated = null,
        public ?string $total_unallocated = null,
        public ?string $reference = null,
        public ?string $status = null,
        public ?string $notes = null,
        public ?int $journal_entry_id = null,
        public ?int $confirmed_by = null,
        public ?string $confirmed_at = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            receipt_number: $data['receipt_number'] ?? null,
            customer_id: $data['customer_id'] ?? null,
            branch_id: $data['branch_id'] ?? null,
            fiscal_year_id: $data['fiscal_year_id'] ?? null,
            receipt_date: $data['receipt_date'] ?? null,
            payment_method: $data['payment_method'] ?? null,
            bank_account_id: $data['bank_account_id'] ?? null,
            currency: $data['currency'] ?? null,
            total_amount: isset($data['total_amount']) ? (string) $data['total_amount'] : null,
            total_allocated: isset($data['total_allocated']) ? (string) $data['total_allocated'] : null,
            total_unallocated: isset($data['total_unallocated']) ? (string) $data['total_unallocated'] : null,
            reference: $data['reference'] ?? null,
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
