<?php

namespace App\Http\Resources\PurchaseOrders;

use App\Http\Resources\Concerns\BuildsProductUnitItemResourceData;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property PurchaseOrder $resource
 */
class PurchaseOrderResource extends JsonResource
{
    use BuildsProductUnitItemResourceData;

    public function toArray($request): array
    {
        /** @var Supplier|null $supplier */
        $supplier = $this->resource->supplier;
        /** @var Warehouse|null $warehouse */
        $warehouse = $this->resource->warehouse;
        /** @var User|null $approver */
        $approver = $this->resource->approver;
        /** @var User|null $creator */
        $creator = $this->resource->creator;

        return [
            'id' => $this->resource->id,
            'po_number' => $this->resource->po_number,
            'supplier' => [
                'id' => $this->resource->supplier_id,
                'name' => $supplier?->name,
            ],
            'warehouse' => [
                'id' => $this->resource->warehouse_id,
                'name' => $warehouse?->name,
            ],
            'order_date' => $this->resource->order_date->toDateString(),
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
                'name' => $approver?->name,
            ] : null,
            'approved_at' => $this->resource->approved_at?->toIso8601String(),
            'created_by' => $this->resource->created_by ? [
                'id' => $this->resource->created_by,
                'name' => $creator?->name,
            ] : null,
            'items' => $this->resource->relationLoaded('items')
                ? $this->resource->items->map(fn (PurchaseOrderItem $item) => $this->productUnitItemResourceData($item, [
                    'purchase_request_item_id' => $item->purchase_request_item_id,
                    'quantity' => (string) $item->quantity,
                    'quantity_received' => (string) $item->quantity_received,
                    'unit_price' => (string) $item->unit_price,
                    'discount_percent' => (string) $item->discount_percent,
                    'tax_percent' => (string) $item->tax_percent,
                    'line_total' => (string) $item->line_total,
                    'notes' => $item->notes,
                ]))->values()
                : [],
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
