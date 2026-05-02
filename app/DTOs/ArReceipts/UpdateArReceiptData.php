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
        $payload = [];

        if ($this->receipt_number !== null) {
            $payload['receipt_number'] = $this->receipt_number;
        }
        if ($this->customer_id !== null) {
            $payload['customer_id'] = $this->customer_id;
        }
        if ($this->branch_id !== null) {
            $payload['branch_id'] = $this->branch_id;
        }
        if ($this->fiscal_year_id !== null) {
            $payload['fiscal_year_id'] = $this->fiscal_year_id;
        }
        if ($this->receipt_date !== null) {
            $payload['receipt_date'] = $this->receipt_date;
        }
        if ($this->payment_method !== null) {
            $payload['payment_method'] = $this->payment_method;
        }
        if ($this->bank_account_id !== null) {
            $payload['bank_account_id'] = $this->bank_account_id;
        }
        if ($this->currency !== null) {
            $payload['currency'] = $this->currency;
        }
        if ($this->total_amount !== null) {
            $payload['total_amount'] = $this->total_amount;
        }
        if ($this->total_allocated !== null) {
            $payload['total_allocated'] = $this->total_allocated;
        }
        if ($this->total_unallocated !== null) {
            $payload['total_unallocated'] = $this->total_unallocated;
        }
        if ($this->reference !== null) {
            $payload['reference'] = $this->reference;
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
