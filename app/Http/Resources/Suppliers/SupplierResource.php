<?php

namespace App\Http\Resources\Suppliers;

use App\Models\Supplier;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Supplier $resource
 */
class SupplierResource extends JsonResource
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
            'email' => $this->resource->email,
            'phone' => $this->resource->phone,
            'address' => $this->resource->address,
            'branch' => [
                'id' => $this->resource->branch_id,
                'name' => $this->resource->branch?->name,
            ],
            'category' => [
                'id' => $this->resource->category_id,
                'name' => $this->resource->category?->name,
            ],
            'status' => $this->resource->status,
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
