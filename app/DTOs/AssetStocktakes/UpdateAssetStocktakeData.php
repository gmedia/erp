<?php

namespace App\DTOs\AssetStocktakes;

readonly class UpdateAssetStocktakeData
{
    public function __construct(
        public ?int $branch_id = null,
        public ?string $reference = null,
        public ?string $planned_at = null,
        public ?string $performed_at = null,
        public ?string $status = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            branch_id: $data['branch_id'] ?? null,
            reference: $data['reference'] ?? null,
            planned_at: $data['planned_at'] ?? null,
            performed_at: $data['performed_at'] ?? null,
            status: $data['status'] ?? null,
        );
    }

    public function toArray(): array
    {
        $data = [];
        
        if ($this->branch_id !== null) $data['branch_id'] = $this->branch_id;
        if ($this->reference !== null) $data['reference'] = $this->reference;
        if ($this->planned_at !== null) $data['planned_at'] = $this->planned_at;
        if ($this->performed_at !== null) $data['performed_at'] = $this->performed_at;
        if ($this->status !== null) $data['status'] = $this->status;

        return $data;
    }
}
