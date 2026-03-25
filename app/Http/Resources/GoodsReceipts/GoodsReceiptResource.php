<?php

namespace App\Http\Resources\GoodsReceipts;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property GoodsReceipt $resource
 */
class GoodsReceiptResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var PurchaseOrder|null $purchaseOrder */
        $purchaseOrder = $this->resource->purchaseOrder;
        /** @var Supplier|null $purchaseOrderSupplier */
        $purchaseOrderSupplier = $purchaseOrder?->supplier;
        /** @var Warehouse|null $warehouse */
        $warehouse = $this->resource->warehouse;
        /** @var User|null $receiver */
        $receiver = $this->resource->receiver;
        /** @var User|null $confirmer */
        $confirmer = $this->resource->confirmer;
        /** @var User|null $creator */
        $creator = $this->resource->creator;

        return [
            'id' => $this->resource->id,
            'gr_number' => $this->resource->gr_number,
            'purchase_order' => $purchaseOrder ? [
                'id' => $this->resource->purchase_order_id,
                'po_number' => $purchaseOrder->po_number,
                'supplier' => [
                    'id' => $purchaseOrder->supplier_id,
                    'name' => $purchaseOrderSupplier?->name,
                ],
            ] : null,
            'warehouse' => [
                'id' => $this->resource->warehouse_id,
                'name' => $warehouse?->name,
            ],
            'receipt_date' => $this->resource->receipt_date->toDateString(),
            'supplier_delivery_note' => $this->resource->supplier_delivery_note,
            'status' => $this->resource->status,
            'notes' => $this->resource->notes,
            'received_by' => $this->resource->received_by ? [
                'id' => $this->resource->received_by,
                'name' => $receiver?->name,
            ] : null,
            'confirmed_by' => $this->resource->confirmed_by ? [
                'id' => $this->resource->confirmed_by,
                'name' => $confirmer?->name,
            ] : null,
            'confirmed_at' => $this->resource->confirmed_at?->toIso8601String(),
            'created_by' => $this->resource->created_by ? [
                'id' => $this->resource->created_by,
                'name' => $creator?->name,
            ] : null,
            'items' => $this->resource->relationLoaded('items')
                ? $this->resource->items->map(static function ($item) {
                    /** @var GoodsReceiptItem $item */
                    /** @var Product|null $product */
                    $product = $item->product;
                    /** @var Unit|null $unit */
                    $unit = $item->unit;

                    return [
                        'id' => $item->id,
                        'purchase_order_item_id' => $item->purchase_order_item_id,
                        'product' => [
                            'id' => $item->product_id,
                            'name' => $product?->name,
                        ],
                        'unit' => [
                            'id' => $item->unit_id,
                            'name' => $unit?->name,
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
