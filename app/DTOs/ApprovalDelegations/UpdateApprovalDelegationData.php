<?php

namespace App\DTOs\ApprovalDelegations;

readonly class UpdateApprovalDelegationData
{
    public function __construct(
        public ?int $delegator_user_id = null,
        public ?int $delegate_user_id = null,
        public ?string $approvable_type = null,
        public ?string $start_date = null,
        public ?string $end_date = null,
        public ?string $reason = null,
        public ?bool $is_active = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            delegator_user_id: $data['delegator_user_id'] ?? null,
            delegate_user_id: $data['delegate_user_id'] ?? null,
            approvable_type: isset($data['approvable_type']) ? $data['approvable_type'] : null,
            start_date: $data['start_date'] ?? null,
            end_date: $data['end_date'] ?? null,
            reason: isset($data['reason']) ? $data['reason'] : null,
            is_active: isset($data['is_active']) ? (bool) $data['is_active'] : null,
        );
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->delegator_user_id !== null) {
            $data['delegator_user_id'] = $this->delegator_user_id;
        }
        if ($this->delegate_user_id !== null) {
            $data['delegate_user_id'] = $this->delegate_user_id;
        }
        if ($this->approvable_type !== null) {
            $data['approvable_type'] = $this->approvable_type;
        }
        if ($this->start_date !== null) {
            $data['start_date'] = $this->start_date;
        }
        if ($this->end_date !== null) {
            $data['end_date'] = $this->end_date;
        }
        if ($this->reason !== null) {
            $data['reason'] = $this->reason;
        }
        if ($this->is_active !== null) {
            $data['is_active'] = $this->is_active;
        }

        return $data;
    }
}
