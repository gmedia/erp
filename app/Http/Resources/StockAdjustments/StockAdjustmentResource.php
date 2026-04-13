<?php

namespace App\Http\Resources\StockAdjustments;

use App\Http\Resources\Concerns\BuildsProductUnitItemResourceData;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property StockAdjustment $resource
 */
/**
 * @mixin \App\Models\StockAdjustment
 */
class StockAdjustmentResource extends JsonResource
{
    use BuildsProductUnitItemResourceData;

    public function toArray($request): array
    {
        $items = [];

        if ($this->resource->relationLoaded('items')) {
            /** @var EloquentCollection<int, StockAdjustmentItem> $stockAdjustmentItems */
            $stockAdjustmentItems = $this->resource->items;

            $items = $this->productUnitItemsResourceData($stockAdjustmentItems, fn ($item) => [
                'quantity_before' => (string) $item->quantity_before,
                'quantity_adjusted' => (string) $item->quantity_adjusted,
                'quantity_after' => (string) $item->quantity_after,
                'unit_cost' => (string) $item->unit_cost,
                'total_cost' => (string) $item->total_cost,
                'reason' => $item->reason,
            ]);
        }

        return [
            'id' => $this->resource->id,
            'adjustment_number' => $this->resource->adjustment_number,
            'warehouse' => [
                'id' => $this->resource->warehouse_id,
                'name' => $this->resource->warehouse?->name,
            ],
            'adjustment_date' => $this->resource->adjustment_date?->toIso8601String(),
            'adjustment_type' => $this->resource->adjustment_type,
            'status' => $this->resource->status,
            'inventory_stocktake' => $this->resource->inventory_stocktake_id ? [
                'id' => $this->resource->inventory_stocktake_id,
                'stocktake_number' => $this->resource->inventoryStocktake?->stocktake_number,
            ] : null,
            'notes' => $this->resource->notes,
            'journal_entry' => $this->resource->journal_entry_id ? [
                'id' => $this->resource->journal_entry_id,
                'entry_number' => $this->resource->journalEntry?->entry_number,
            ] : null,
            'approved_by' => $this->resource->approvedBy ? [
                'id' => $this->resource->approvedBy->id,
                'name' => $this->resource->approvedBy->name,
            ] : null,
            'approved_at' => $this->resource->approved_at?->toIso8601String(),
            'created_by' => $this->resource->createdBy ? [
                'id' => $this->resource->createdBy->id,
                'name' => $this->resource->createdBy->name,
            ] : null,
            'items' => $items,
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
