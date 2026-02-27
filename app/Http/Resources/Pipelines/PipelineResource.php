<?php

namespace App\Http\Resources\Pipelines;

use App\Models\Pipeline;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property Pipeline $resource */
class PipelineResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'code' => $this->resource->code,
            'entity_type' => $this->resource->entity_type,
            'description' => $this->resource->description,
            'version' => $this->resource->version,
            'is_active' => $this->resource->is_active,
            'conditions' => $this->resource->conditions ? json_encode($this->resource->conditions) : null,
            'created_by' => $this->resource->creator ? [
                'id' => $this->resource->creator->id,
                'name' => $this->resource->creator->name,
            ] : null,
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
