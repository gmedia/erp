<?php

namespace App\Http\Resources\Suppliers;

use Illuminate\Http\Request;
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
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'branch' => [
                'id' => $this->resource->branch_id,
                'name' => $this->resource->branch?->name,
            ],
            'category' => [
                'id' => $this->resource->category_id,
                'name' => $this->resource->category?->name,
            ],
        ];
    }
}
