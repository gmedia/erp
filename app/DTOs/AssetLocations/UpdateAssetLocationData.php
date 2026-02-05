<?php

namespace App\DTOs\AssetLocations;

readonly class UpdateAssetLocationData
{
    public function __construct(
        public ?int $branch_id = null,
        public ?int $parent_id = null,
        public ?string $code = null,
        public ?string $name = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            branch_id: $data['branch_id'] ?? null,
            parent_id: array_key_exists('parent_id', $data) ? $data['parent_id'] : null,
            code: $data['code'] ?? null,
            name: $data['name'] ?? null,
        );
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->branch_id !== null) {
            $data['branch_id'] = $this->branch_id;
        }
        if ($this->parent_id !== null) {
            $data['parent_id'] = $this->parent_id;
        }
        if ($this->code !== null) {
            $data['code'] = $this->code;
        }
        if ($this->name !== null) {
            $data['name'] = $this->name;
        }

        return $data;
    }
}
