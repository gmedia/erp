<?php

namespace App\Http\Resources\CustomerCategories;

use App\Models\CustomerCategory;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property CustomerCategory $resource
 */
class CustomerCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
