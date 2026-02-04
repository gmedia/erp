<?php

namespace App\DTOs\AssetModels;

readonly class UpdateAssetModelData
{
    public function __construct(
        public ?int $asset_category_id = null,
        public ?string $manufacturer = null,
        public ?string $model_name = null,
        public ?array $specs = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            asset_category_id: $data['asset_category_id'] ?? null,
            manufacturer: $data['manufacturer'] ?? null,
            model_name: $data['model_name'] ?? null,
            specs: $data['specs'] ?? null,
        );
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->asset_category_id !== null) {
            $data['asset_category_id'] = $this->asset_category_id;
        }
        if ($this->manufacturer !== null) {
            $data['manufacturer'] = $this->manufacturer;
        }
        if ($this->model_name !== null) {
            $data['model_name'] = $this->model_name;
        }
        if ($this->specs !== null) {
            $data['specs'] = $this->specs;
        }

        return $data;
    }
}
