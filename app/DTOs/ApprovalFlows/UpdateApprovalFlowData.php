<?php

namespace App\DTOs\ApprovalFlows;

readonly class UpdateApprovalFlowData
{
    public function __construct(
        public ?string $name = null,
        public ?string $code = null,
        public ?string $approvable_type = null,
        public ?string $description = null,
        public ?bool $is_active = null,
        public ?array $conditions = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            code: $data['code'] ?? null,
            approvable_type: $data['approvable_type'] ?? null,
            description: $data['description'] ?? null,
            is_active: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            conditions: array_key_exists('conditions', $data) ? $data['conditions'] : null,
        );
    }

    public function toArray(): array
    {
        $data = [];
        
        if ($this->name !== null) $data['name'] = $this->name;
        if ($this->code !== null) $data['code'] = $this->code;
        if ($this->approvable_type !== null) $data['approvable_type'] = $this->approvable_type;
        if ($this->description !== null) $data['description'] = $this->description;
        if ($this->is_active !== null) $data['is_active'] = $this->is_active;
        if ($this->conditions !== null) $data['conditions'] = $this->conditions; // Could be null intentionally to clear

        return $data;
    }
}
