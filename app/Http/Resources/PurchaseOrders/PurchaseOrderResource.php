<?php

namespace App\Http\Resources\PurchaseOrders;

use App\Models\PurchaseOrder;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property PurchaseOrder $resource
 */
class PurchaseOrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'po_number' => $this->resource->po_number,
            'supplier' => [
                'id' => $this->resource->supplier_id,
                'name' => $this->resource->supplier?->name,
            ],
            'warehouse' => [
                'id' => $this->resource->warehouse_id,
                'name' => $this->resource->warehouse?->name,
            ],
            'order_date' => $this->resource->order_date?->toDateString(),
            'expected_delivery_date' => $this->resource->expected_delivery_date?->toDateString(),
            'payment_terms' => $this->resource->payment_terms,
            'currency' => $this->resource->currency,
            'subtotal' => (string) $this->resource->subtotal,
            'tax_amount' => (string) $this->resource->tax_amount,
            'discount_amount' => (string) $this->resource->discount_amount,
            'grand_total' => (string) $this->resource->grand_total,
            'status' => $this->resource->status,
            'notes' => $this->resource->notes,
            'shipping_address' => $this->resource->shipping_address,
            'approved_by' => $this->resource->approved_by ? [
                'id' => $this->resource->approved_by,
                'name' => $this->resource->approver?->name,
            ] : null,
            'approved_at' => $this->resource->approved_at?->toIso8601String(),
            'created_by' => $this->resource->created_by ? [
                'id' => $this->resource->created_by,
                'name' => $this->resource->creator?->name,
            ] : null,
            'items' => $this->resource->relationLoaded('items')
                ? $this->resource->items->map(static function ($item) {
                    return [
                        'id' => $item->id,
                        'purchase_request_item_id' => $item->purchase_request_item_id,
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
                        'unit_price' => (string) $item->unit_price,
                        'discount_percent' => (string) $item->discount_percent,
                        'tax_percent' => (string) $item->tax_percent,
                        'line_total' => (string) $item->line_total,
                        'notes' => $item->notes,
                    ];
                })->values()
                : [],
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
