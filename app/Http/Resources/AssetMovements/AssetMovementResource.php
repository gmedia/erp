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
            'movement_type' => $this->movement_type,
            'moved_at' => $this->moved_at?->toIso8601String(),
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
