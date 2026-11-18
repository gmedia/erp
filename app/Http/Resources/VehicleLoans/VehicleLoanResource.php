<?php

namespace App\Http\Resources\VehicleLoans;

use App\Http\Resources\SimpleCrudResource;

class VehicleLoanResource extends SimpleCrudResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'code' => $this->resource->code,
            'requester_id' => $this->resource->requester_id,
            'requester_name' => $this->resource->requester?->name,
            'department_id' => $this->resource->department_id,
            'department_name' => $this->resource->department?->name,
            'branch_id' => $this->resource->branch_id,
            'company_id' => $this->resource->company_id,
            'start_at' => $this->resource->start_at?->toIso8601String(),
            'end_at' => $this->resource->end_at?->toIso8601String(),
            'purpose' => $this->resource->purpose,
            'driver_id' => $this->resource->driver_id,
            'driver_name' => $this->resource->driver?->employee?->name,
            'vehicle_unit_id' => $this->resource->vehicle_unit_id,
            'plate_number' => $this->resource->vehicleUnit?->plate_number,
            'status' => $this->resource->status,
            'submitted_at' => $this->resource->submitted_at?->toIso8601String(),
            'approved_at' => $this->resource->approved_at?->toIso8601String(),
            'rejection_reason' => $this->resource->rejection_reason,
            'review_notes' => $this->resource->review_notes,
            'pickup' => $this->resource->pickup,
            'returns' => $this->resource->returns,
            'created_at' => $this->resource->created_at?->toIso8601String(),
        ];
    }
}
