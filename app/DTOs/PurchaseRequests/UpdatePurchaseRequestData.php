<?php

namespace App\DTOs\PurchaseRequests;

readonly class UpdatePurchaseRequestData
{
    public function __construct(
        public ?string $pr_number = null,
        public ?int $branch_id = null,
        public ?int $department_id = null,
        public ?int $requested_by = null,
        public ?string $request_date = null,
        public ?string $required_date = null,
        public ?string $priority = null,
        public ?string $status = null,
        public ?string $estimated_amount = null,
        public ?string $notes = null,
        public ?int $approved_by = null,
        public ?string $approved_at = null,
        public ?string $rejection_reason = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            pr_number: $data['pr_number'] ?? null,
            branch_id: $data['branch_id'] ?? null,
            department_id: $data['department_id'] ?? null,
            requested_by: $data['requested_by'] ?? null,
            request_date: $data['request_date'] ?? null,
            required_date: $data['required_date'] ?? null,
            priority: $data['priority'] ?? null,
            status: $data['status'] ?? null,
            estimated_amount: isset($data['estimated_amount']) ? (string) $data['estimated_amount'] : null,
            notes: $data['notes'] ?? null,
            approved_by: $data['approved_by'] ?? null,
            approved_at: $data['approved_at'] ?? null,
            rejection_reason: $data['rejection_reason'] ?? null,
        );
    }

    public function toArray(): array
    {
        $payload = [];

        if ($this->pr_number !== null) {
            $payload['pr_number'] = $this->pr_number;
        }
        if ($this->branch_id !== null) {
            $payload['branch_id'] = $this->branch_id;
        }
        if ($this->department_id !== null) {
            $payload['department_id'] = $this->department_id;
        }
        if ($this->requested_by !== null) {
            $payload['requested_by'] = $this->requested_by;
        }
        if ($this->request_date !== null) {
            $payload['request_date'] = $this->request_date;
        }
        if ($this->required_date !== null) {
            $payload['required_date'] = $this->required_date;
        }
        if ($this->priority !== null) {
            $payload['priority'] = $this->priority;
        }
        if ($this->status !== null) {
            $payload['status'] = $this->status;
        }
        if ($this->estimated_amount !== null) {
            $payload['estimated_amount'] = $this->estimated_amount;
        }
        if ($this->notes !== null) {
            $payload['notes'] = $this->notes;
        }
        if ($this->approved_by !== null) {
            $payload['approved_by'] = $this->approved_by;
        }
        if ($this->approved_at !== null) {
            $payload['approved_at'] = $this->approved_at;
        }
        if ($this->rejection_reason !== null) {
            $payload['rejection_reason'] = $this->rejection_reason;
        }

        return $payload;
    }
}
