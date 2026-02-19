<?php

namespace App\Http\Resources\AssetMaintenances;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetMaintenanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asset_id' => $this->asset_id,
            'asset' => [
                'id' => $this->asset_id,
                'name' => $this->asset?->name,
                'asset_code' => $this->asset?->asset_code,
            ],
            'maintenance_type' => $this->maintenance_type,
            'status' => $this->status,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'performed_at' => $this->performed_at?->toIso8601String(),
            'supplier_id' => $this->supplier_id,
            'supplier' => $this->supplier?->name,
            'cost' => (string) $this->cost,
            'notes' => $this->notes,
            'created_by_id' => $this->created_by,
            'created_by' => $this->createdBy?->name,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
