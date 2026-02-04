<?php

namespace App\Http\Resources\AssetCategories;

use App\Http\Resources\SimpleCrudResource;

class AssetCategoryResource extends SimpleCrudResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'code' => $this->resource->code,
            'name' => $this->resource->name,
            'useful_life_months_default' => $this->resource->useful_life_months_default,
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
