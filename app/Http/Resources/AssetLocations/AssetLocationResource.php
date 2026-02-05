<?php

namespace App\Http\Resources\AssetLocations;

use App\Models\AssetLocation;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property AssetLocation $resource */
class AssetLocationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'code' => $this->resource->code,
            'name' => $this->resource->name,
            'branch' => [
                'id' => $this->resource->branch_id,
                'name' => $this->resource->branch?->name,
            ],
            'parent' => $this->resource->parent_id ? [
                'id' => $this->resource->parent_id,
                'name' => $this->resource->parent?->name,
            ] : null,
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
