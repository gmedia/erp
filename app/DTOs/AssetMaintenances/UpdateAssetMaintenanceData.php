<?php

namespace App\DTOs\AssetMaintenances;

readonly class UpdateAssetMaintenanceData
{
    public function __construct(
        public ?int $asset_id = null,
        public ?string $maintenance_type = null,
        public ?string $status = null,
        public ?string $scheduled_at = null,
        public ?string $performed_at = null,
        public ?int $supplier_id = null,
        public ?string $cost = null,
        public ?string $notes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            asset_id: $data['asset_id'] ?? null,
            maintenance_type: $data['maintenance_type'] ?? null,
            status: $data['status'] ?? null,
            scheduled_at: $data['scheduled_at'] ?? null,
            performed_at: $data['performed_at'] ?? null,
            supplier_id: $data['supplier_id'] ?? null,
            cost: isset($data['cost']) ? (string) $data['cost'] : null,
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        $data = [];

        foreach (get_object_vars($this) as $key => $value) {
            if ($value !== null) {
                $data[$key] = $value;
            }
        }

        return $data;
    }
}
