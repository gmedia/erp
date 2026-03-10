<?php

namespace App\Http\Resources\SupplierReturns;

use App\Models\SupplierReturn;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property SupplierReturn $resource
 */
class SupplierReturnResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'return_number' => $this->resource->return_number,
            'purchase_order' => $this->resource->purchaseOrder ? [
                'id' => $this->resource->purchase_order_id,
                'po_number' => $this->resource->purchaseOrder?->po_number,
            ] : null,
            'goods_receipt' => $this->resource->goodsReceipt ? [
                'id' => $this->resource->goods_receipt_id,
                'gr_number' => $this->resource->goodsReceipt?->gr_number,
            ] : null,
            'supplier' => [
                'id' => $this->resource->supplier_id,
                'name' => $this->resource->supplier?->name,
            ],
            'warehouse' => [
                'id' => $this->resource->warehouse_id,
                'name' => $this->resource->warehouse?->name,
            ],
            'return_date' => $this->resource->return_date?->toDateString(),
            'reason' => $this->resource->reason,
            'status' => $this->resource->status,
            'notes' => $this->resource->notes,
            'created_by' => $this->resource->created_by ? [
                'id' => $this->resource->created_by,
                'name' => $this->resource->creator?->name,
            ] : null,
            'items' => $this->resource->relationLoaded('items')
                ? $this->resource->items->map(static function ($item) {
                    return [
                        'id' => $item->id,
                        'goods_receipt_item_id' => $item->goods_receipt_item_id,
                        'product' => [
                            'id' => $item->product_id,
                            'name' => $item->product?->name,
                        ],
                        'unit' => $item->unit_id ? [
                            'id' => $item->unit_id,
                            'name' => $item->unit?->name,
                        ] : null,
                        'quantity_returned' => (string) $item->quantity_returned,
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
