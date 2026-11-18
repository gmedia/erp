<?php

namespace App\Http\Resources\VehicleDrivers;

use App\Http\Resources\SimpleCrudResource;

class VehicleDriverResource extends SimpleCrudResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'employee_id' => $this->resource->employee_id,
            'employee_name' => $this->resource->employee?->name,
            'license_number' => $this->resource->license_number,
            'license_type' => $this->resource->license_type,
            'license_expiry' => $this->resource->license_expiry?->toDateString(),
            'is_active' => $this->resource->is_active,
        ];
    }
}
