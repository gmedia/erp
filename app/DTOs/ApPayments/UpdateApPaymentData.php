<?php

namespace App\DTOs\ApPayments;

readonly class UpdateApPaymentData
{
    public function __construct(
        public ?string $payment_number = null,
        public ?int $supplier_id = null,
        public ?int $branch_id = null,
        public ?int $fiscal_year_id = null,
        public ?string $payment_date = null,
        public ?string $payment_method = null,
        public ?int $bank_account_id = null,
        public ?string $currency = null,
        public ?string $total_amount = null,
        public ?string $reference = null,
        public ?string $status = null,
        public ?string $notes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            payment_number: $data['payment_number'] ?? null,
            supplier_id: $data['supplier_id'] ?? null,
            branch_id: $data['branch_id'] ?? null,
            fiscal_year_id: $data['fiscal_year_id'] ?? null,
            payment_date: $data['payment_date'] ?? null,
            payment_method: $data['payment_method'] ?? null,
            bank_account_id: $data['bank_account_id'] ?? null,
            currency: $data['currency'] ?? null,
            total_amount: isset($data['total_amount']) ? (string) $data['total_amount'] : null,
            reference: $data['reference'] ?? null,
            status: $data['status'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        $payload = [];

        if ($this->payment_number !== null) {
            $payload['payment_number'] = $this->payment_number;
        }
        if ($this->supplier_id !== null) {
            $payload['supplier_id'] = $this->supplier_id;
        }
        if ($this->branch_id !== null) {
            $payload['branch_id'] = $this->branch_id;
        }
        if ($this->fiscal_year_id !== null) {
            $payload['fiscal_year_id'] = $this->fiscal_year_id;
        }
        if ($this->payment_date !== null) {
            $payload['payment_date'] = $this->payment_date;
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
        if ($this->reference !== null) {
            $payload['reference'] = $this->reference;
        }
        if ($this->status !== null) {
            $payload['status'] = $this->status;
        }
        if ($this->notes !== null) {
            $payload['notes'] = $this->notes;
        }

        return $payload;
    }
}
