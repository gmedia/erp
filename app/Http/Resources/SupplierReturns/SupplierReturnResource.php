<?php

namespace App\Http\Resources\SupplierReturns;

use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierReturn;
use App\Models\SupplierReturnItem;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property SupplierReturn $resource
 */
class SupplierReturnResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var PurchaseOrder|null $purchaseOrder */
        $purchaseOrder = $this->resource->purchaseOrder;
        /** @var GoodsReceipt|null $goodsReceipt */
        $goodsReceipt = $this->resource->goodsReceipt;
        /** @var Supplier|null $supplier */
        $supplier = $this->resource->supplier;
        /** @var Warehouse|null $warehouse */
        $warehouse = $this->resource->warehouse;
        /** @var User|null $creator */
        $creator = $this->resource->creator;

        return [
            'id' => $this->resource->id,
            'return_number' => $this->resource->return_number,
            'purchase_order' => $purchaseOrder ? [
                'id' => $this->resource->purchase_order_id,
                'po_number' => $purchaseOrder->po_number,
            ] : null,
            'goods_receipt' => $goodsReceipt ? [
                'id' => $this->resource->goods_receipt_id,
                'gr_number' => $goodsReceipt->gr_number,
            ] : null,
            'supplier' => [
                'id' => $this->resource->supplier_id,
                'name' => $supplier?->name,
            ],
            'warehouse' => [
                'id' => $this->resource->warehouse_id,
                'name' => $warehouse?->name,
            ],
            'return_date' => $this->resource->return_date->toDateString(),
            'reason' => $this->resource->reason,
            'status' => $this->resource->status,
            'notes' => $this->resource->notes,
            'created_by' => $this->resource->created_by ? [
                'id' => $this->resource->created_by,
                'name' => $creator?->name,
            ] : null,
            'items' => $this->resource->relationLoaded('items')
                ? $this->resource->items->map(static function ($item) {
                    /** @var SupplierReturnItem $item */
                    /** @var Product|null $product */
                    $product = $item->product;
                    /** @var Unit|null $unit */
                    $unit = $item->unit;

                    return [
                        'id' => $item->id,
                        'goods_receipt_item_id' => $item->goods_receipt_item_id,
                        'product' => [
                            'id' => $item->product_id,
                            'name' => $product?->name,
                        ],
                        'unit' => $item->unit_id ? [
                            'id' => $item->unit_id,
                            'name' => $unit?->name,
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
