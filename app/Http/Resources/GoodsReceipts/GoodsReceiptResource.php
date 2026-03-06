<?php

namespace App\Http\Resources\GoodsReceipts;

use App\Models\GoodsReceipt;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property GoodsReceipt $resource
 */
class GoodsReceiptResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'gr_number' => $this->resource->gr_number,
            'purchase_order' => $this->resource->purchaseOrder ? [
                'id' => $this->resource->purchase_order_id,
                'po_number' => $this->resource->purchaseOrder?->po_number,
                'supplier' => [
                    'id' => $this->resource->purchaseOrder?->supplier_id,
                    'name' => $this->resource->purchaseOrder?->supplier?->name,
                ],
            ] : null,
            'warehouse' => [
                'id' => $this->resource->warehouse_id,
                'name' => $this->resource->warehouse?->name,
            ],
            'receipt_date' => $this->resource->receipt_date?->toDateString(),
            'supplier_delivery_note' => $this->resource->supplier_delivery_note,
            'status' => $this->resource->status,
            'notes' => $this->resource->notes,
            'received_by' => $this->resource->received_by ? [
                'id' => $this->resource->received_by,
                'name' => $this->resource->receiver?->name,
            ] : null,
            'confirmed_by' => $this->resource->confirmed_by ? [
                'id' => $this->resource->confirmed_by,
                'name' => $this->resource->confirmer?->name,
            ] : null,
            'confirmed_at' => $this->resource->confirmed_at?->toIso8601String(),
            'created_by' => $this->resource->created_by ? [
                'id' => $this->resource->created_by,
                'name' => $this->resource->creator?->name,
            ] : null,
            'items' => $this->resource->relationLoaded('items')
                ? $this->resource->items->map(static function ($item) {
                    return [
                        'id' => $item->id,
                        'purchase_order_item_id' => $item->purchase_order_item_id,
                        'product' => [
                            'id' => $item->product_id,
                            'name' => $item->product?->name,
                        ],
                        'unit' => [
                            'id' => $item->unit_id,
                            'name' => $item->unit?->name,
                        ],
                        'quantity_received' => (string) $item->quantity_received,
                        'quantity_accepted' => (string) $item->quantity_accepted,
                        'quantity_rejected' => (string) $item->quantity_rejected,
                        'unit_price' => (string) $item->unit_price,
                        'notes' => $item->notes,
                    ];
                })->values()
                : [],
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
