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
            'maintenance_type' => $this->maintenance_type,
            'status' => $this->status,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'performed_at' => $this->performed_at?->toIso8601String(),
            'supplier' => $this->supplier?->name,
            'cost' => (string) $this->cost,
            'notes' => $this->notes,
            'created_by' => $this->createdBy?->name,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
