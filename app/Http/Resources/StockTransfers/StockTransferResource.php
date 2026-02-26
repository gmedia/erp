<?php

namespace App\Http\Resources\StockTransfers;

use App\Models\StockTransfer;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property StockTransfer $resource
 */
class StockTransferResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'transfer_number' => $this->resource->transfer_number,
            'from_warehouse' => [
                'id' => $this->resource->from_warehouse_id,
                'name' => $this->resource->fromWarehouse?->name,
            ],
            'to_warehouse' => [
                'id' => $this->resource->to_warehouse_id,
                'name' => $this->resource->toWarehouse?->name,
            ],
            'transfer_date' => $this->resource->transfer_date?->toIso8601String(),
            'expected_arrival_date' => $this->resource->expected_arrival_date?->toIso8601String(),
            'status' => $this->resource->status,
            'notes' => $this->resource->notes,
            'requested_by' => $this->resource->requestedBy ? [
                'id' => $this->resource->requestedBy->id,
                'name' => $this->resource->requestedBy->name,
            ] : null,
            'approved_by' => $this->resource->approvedBy ? [
                'id' => $this->resource->approvedBy->id,
                'name' => $this->resource->approvedBy->name,
            ] : null,
            'approved_at' => $this->resource->approved_at?->toIso8601String(),
            'shipped_by' => $this->resource->shippedBy ? [
                'id' => $this->resource->shippedBy->id,
                'name' => $this->resource->shippedBy->name,
            ] : null,
            'shipped_at' => $this->resource->shipped_at?->toIso8601String(),
            'received_by' => $this->resource->receivedBy ? [
                'id' => $this->resource->receivedBy->id,
                'name' => $this->resource->receivedBy->name,
            ] : null,
            'received_at' => $this->resource->received_at?->toIso8601String(),
            'created_by' => $this->resource->createdBy ? [
                'id' => $this->resource->createdBy->id,
                'name' => $this->resource->createdBy->name,
            ] : null,
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
                    'quantity' => (string) $item->quantity,
                    'quantity_received' => (string) $item->quantity_received,
                    'unit_cost' => (string) $item->unit_cost,
                    'notes' => $item->notes,
                    'created_at' => $item->created_at?->toIso8601String(),
                    'updated_at' => $item->updated_at?->toIso8601String(),
                ])->values()
                : [],
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
