<?php

namespace App\Http\Resources\Concerns;

trait BuildsStockMovementInventoryResourceData
{
    /**
     * @return array<string, mixed>
     */
    protected function stockMovementInventoryResourceData(bool $includeUnit = false): array
    {
        return array_merge([
            'id' => $this->resource->id,
            'product' => $this->stockMovementProductResourceData($includeUnit),
            'warehouse' => $this->stockMovementWarehouseResourceData(),
        ], $this->stockMovementInventorySummaryData());
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function stockMovementProductResourceData(bool $includeUnit = false): ?array
    {
        if (! $this->resource->product) {
            return null;
        }

        $product = [
            'id' => $this->resource->product->id,
            'code' => $this->resource->product->code,
            'name' => $this->resource->product->name,
            'category' => $this->resource->product->category ? [
                'id' => $this->resource->product->category->id,
                'name' => $this->resource->product->category->name,
            ] : null,
        ];

        if ($includeUnit) {
            $product['unit'] = $this->resource->product->unit ? [
                'id' => $this->resource->product->unit->id,
                'name' => $this->resource->product->unit->name,
            ] : null;
        }

        return $product;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function stockMovementWarehouseResourceData(): ?array
    {
        if (! $this->resource->warehouse) {
            return null;
        }

        return [
            'id' => $this->resource->warehouse->id,
            'code' => $this->resource->warehouse->code,
            'name' => $this->resource->warehouse->name,
            'branch' => $this->resource->warehouse->branch ? [
                'id' => $this->resource->warehouse->branch->id,
                'name' => $this->resource->warehouse->branch->name,
            ] : null,
        ];
    }

    /**
     * @return array<string, string|null>
     */
    protected function stockMovementInventorySummaryData(): array
    {
        return [
            'quantity_on_hand' => (string) $this->resource->quantity_on_hand,
            'average_cost' => (string) $this->resource->average_cost,
            'stock_value' => (string) $this->resource->stock_value,
            'moved_at' => $this->resource->moved_at?->toIso8601String(),
        ];
    }
}
