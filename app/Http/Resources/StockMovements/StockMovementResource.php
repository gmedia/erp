<?php

namespace App\Http\Resources\StockMovements;

use App\Models\StockMovement;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property StockMovement $resource
 */
class StockMovementResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'product' => $this->resource->product ? [
                'id' => $this->resource->product->id,
                'code' => $this->resource->product->code,
                'name' => $this->resource->product->name,
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
            'movement_type' => $this->resource->movement_type,
            'quantity_in' => (string) $this->resource->quantity_in,
            'quantity_out' => (string) $this->resource->quantity_out,
            'balance_after' => (string) $this->resource->balance_after,
            'unit_cost' => $this->resource->unit_cost !== null ? (string) $this->resource->unit_cost : null,
            'average_cost_after' => $this->resource->average_cost_after !== null ? (string) $this->resource->average_cost_after : null,
            'reference_type' => $this->resource->reference_type,
            'reference_id' => $this->resource->reference_id,
            'reference_number' => $this->resource->reference_number,
            'notes' => $this->resource->notes,
            'moved_at' => $this->resource->moved_at?->toIso8601String(),
            'created_by' => $this->resource->createdBy ? [
                'id' => $this->resource->createdBy->id,
                'name' => $this->resource->createdBy->name,
                'email' => $this->resource->createdBy->email,
            ] : null,
            'created_at' => $this->resource->created_at?->toIso8601String(),
        ];
    }
}

