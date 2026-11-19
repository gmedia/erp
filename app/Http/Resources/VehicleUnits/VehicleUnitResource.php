<?php

namespace App\Http\Resources\VehicleUnits;

use App\Http\Resources\SimpleCrudResource;

class VehicleUnitResource extends SimpleCrudResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'asset_id' => $this->resource->asset_id,
            'asset_name' => $this->resource->asset?->name,
            'plate_number' => $this->resource->plate_number,
            'vehicle_type' => $this->resource->vehicle_type,
            'capacity' => $this->resource->capacity,
            'is_active' => $this->resource->is_active,
        ];
    }
}
