<?php

namespace App\Http\Resources\InventoryStocktakes;

use App\Models\InventoryStocktake;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property InventoryStocktake $resource
 */
class InventoryStocktakeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'stocktake_number' => $this->resource->stocktake_number,
            'warehouse' => [
                'id' => $this->resource->warehouse_id,
                'name' => $this->resource->warehouse?->name,
            ],
            'stocktake_date' => $this->resource->stocktake_date?->toIso8601String(),
            'status' => $this->resource->status,
            'product_category' => $this->resource->product_category_id ? [
                'id' => $this->resource->product_category_id,
                'name' => $this->resource->productCategory?->name,
            ] : null,
            'notes' => $this->resource->notes,
            'created_by' => $this->resource->createdBy ? [
                'id' => $this->resource->createdBy->id,
                'name' => $this->resource->createdBy->name,
            ] : null,
            'completed_by' => $this->resource->completedBy ? [
                'id' => $this->resource->completedBy->id,
                'name' => $this->resource->completedBy->name,
            ] : null,
            'completed_at' => $this->resource->completed_at?->toIso8601String(),
            'items' => $this->resource->relationLoaded('items')
                ? $this->resource->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product' => [
                        'id' => $item->product_id,
                        'name' => $item->product?->name,
                    ],
                    'unit' => [
                        'id' => $item->unit_id,
                        'name' => $item->unit?->name,
                    ],
                    'system_quantity' => (string) $item->system_quantity,
                    'counted_quantity' => $item->counted_quantity === null ? null : (string) $item->counted_quantity,
                    'variance' => $item->variance === null ? null : (string) $item->variance,
                    'result' => $item->result,
                    'notes' => $item->notes,
                    'counted_by' => $item->countedBy ? [
                        'id' => $item->countedBy->id,
                        'name' => $item->countedBy->name,
                    ] : null,
                    'counted_at' => $item->counted_at?->toIso8601String(),
                    'created_at' => $item->created_at?->toIso8601String(),
                    'updated_at' => $item->updated_at?->toIso8601String(),
                ])->values()
                : [],
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}

