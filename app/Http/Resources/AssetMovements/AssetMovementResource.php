<?php

namespace App\Http\Resources\AssetMovements;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetMovementResource extends JsonResource
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
            'movement_type' => $this->movement_type,
            'moved_at' => $this->moved_at?->toIso8601String(),
            'from_branch_id' => $this->from_branch_id,
            'to_branch_id' => $this->to_branch_id,
            'from_location_id' => $this->from_location_id,
            'to_location_id' => $this->to_location_id,
            'from_department_id' => $this->from_department_id,
            'to_department_id' => $this->to_department_id,
            'from_employee_id' => $this->from_employee_id,
            'to_employee_id' => $this->to_employee_id,
            'from_branch' => $this->fromBranch?->name,
            'to_branch' => $this->toBranch?->name,
            'from_location' => $this->fromLocation?->name,
            'to_location' => $this->toLocation?->name,
            'from_department' => $this->fromDepartment?->name,
            'to_department' => $this->toDepartment?->name,
            'from_employee' => $this->fromEmployee?->name,
            'to_employee' => $this->toEmployee?->name,
            'reference' => $this->reference,
            'notes' => $this->notes,
            'created_by' => $this->createdBy?->name,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
