<?php

namespace App\Http\Resources\AssetModels;

use App\Models\AssetModel;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property AssetModel $resource */
class AssetModelResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->model_name,
            'model_name' => $this->resource->model_name,
            'manufacturer' => $this->resource->manufacturer,
            'specs' => $this->resource->specs,
            'category' => [
                'id' => $this->resource->asset_category_id,
                'name' => $this->resource->category?->name,
            ],
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
