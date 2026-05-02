<?php

namespace App\Http\Resources\Products;

use App\Models\Product;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property Product $resource */
/**
 * @mixin \App\Models\Product
 */
class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'code' => $this->resource->code,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'type' => $this->resource->type,

            'category' => [
                'id' => $this->resource->product_category_id,
                'name' => $this->resource->category?->name,
            ],
            'unit' => [
                'id' => $this->resource->unit_id,
                'name' => $this->resource->unit?->name,
                'symbol' => $this->resource->unit?->symbol,
            ],
            'branch' => $this->resource->branch_id ? [
                'id' => $this->resource->branch_id,
                'name' => $this->resource->branch?->name,
            ] : null,

            'cost' => (string) $this->resource->cost,
            'selling_price' => (string) $this->resource->selling_price,

            'billing_model' => $this->resource->billing_model,

            'status' => $this->resource->status,
            'notes' => $this->resource->notes,

            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
