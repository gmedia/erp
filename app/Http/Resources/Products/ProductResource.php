<?php

namespace App\Http\Resources\Products;

use App\Models\Product;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property Product $resource */
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
            
            // Nested Objects
            'category' => [
                'id' => $this->resource->category_id,
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
            'markup_percentage' => (string) $this->resource->markup_percentage,
            
            'billing_model' => $this->resource->billing_model,
            'is_recurring' => $this->resource->is_recurring,
            'trial_period_days' => $this->resource->trial_period_days,
            'allow_one_time_purchase' => $this->resource->allow_one_time_purchase,
            
            'is_manufactured' => $this->resource->is_manufactured,
            'is_purchasable' => $this->resource->is_purchasable,
            'is_sellable' => $this->resource->is_sellable,
            'is_taxable' => $this->resource->is_taxable,
            
            'status' => $this->resource->status,
            'notes' => $this->resource->notes,
            
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
