<?php

namespace App\DTOs\Pipelines;

readonly class UpdatePipelineData
{
    public function __construct(
        public ?string $name = null,
        public ?string $code = null,
        public ?string $entity_type = null,
        public ?string $description = null,
        public ?int $version = null,
        public ?bool $is_active = null,
        public ?string $conditions = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            code: $data['code'] ?? null,
            entity_type: $data['entity_type'] ?? null,
            description: $data['description'] ?? null,
            version: $data['version'] ?? null,
            is_active: $data['is_active'] ?? null,
            conditions: $data['conditions'] ?? null,
        );
    }

    public function toArray(): array
    {
        $data = [];
        
        if ($this->name !== null) $data['name'] = $this->name;
        if ($this->code !== null) $data['code'] = $this->code;
        if ($this->entity_type !== null) $data['entity_type'] = $this->entity_type;
        $data['description'] = $this->description; // can be null
        if ($this->version !== null) $data['version'] = $this->version;
        if ($this->is_active !== null) $data['is_active'] = $this->is_active;
        $data['conditions'] = $this->conditions ? json_decode($this->conditions, true) : null;

        return $data;
    }
}
