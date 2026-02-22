<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceCostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asset_id' => $this->asset_id,
            'asset_code' => $this->asset?->asset_code,
            'asset_name' => $this->asset?->name,
            'asset_category_id' => $this->asset?->asset_category_id,
            'category_name' => $this->asset?->category?->name,
            'branch_id' => $this->asset?->branch_id,
            'branch_name' => $this->asset?->branch?->name,
            'maintenance_type' => $this->maintenance_type,
            'status' => $this->status,
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'performed_at' => $this->performed_at?->toISOString(),
            'supplier_id' => $this->supplier_id,
            'supplier_name' => $this->supplier?->name,
            'cost' => (float) $this->cost,
            'notes' => $this->notes,
        ];
    }
}
