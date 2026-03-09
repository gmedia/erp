<?php

namespace App\Http\Resources\Reports;

use App\Models\StockMovement;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property StockMovement $resource
 */
/**
 * @mixin \App\Models\StockMovement
 */
class InventoryValuationReportResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'product' => $this->resource->product ? [
                'id' => $this->resource->product->id,
                'code' => $this->resource->product->code,
                'name' => $this->resource->product->name,
                'category' => $this->resource->product->category ? [
                    'id' => $this->resource->product->category->id,
                    'name' => $this->resource->product->category->name,
                ] : null,
                'unit' => $this->resource->product->unit ? [
                    'id' => $this->resource->product->unit->id,
                    'name' => $this->resource->product->unit->name,
                ] : null,
            ] : null,
            'warehouse' => $this->resource->warehouse ? [
                'id' => $this->resource->warehouse->id,
                'code' => $this->resource->warehouse->code,
                'name' => $this->resource->warehouse->name,
                'branch' => $this->resource->warehouse->branch ? [
                    'id' => $this->resource->warehouse->branch->id,
                    'name' => $this->resource->warehouse->branch->name,
                ] : null,
            ] : null,
            'quantity_on_hand' => (string) $this->resource->quantity_on_hand,
            'average_cost' => (string) $this->resource->average_cost,
            'stock_value' => (string) $this->resource->stock_value,
            'moved_at' => $this->resource->moved_at?->toIso8601String(),
        ];
    }
}
