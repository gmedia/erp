<?php

namespace App\Http\Resources\Reports;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $purchase_order_item_id
 * @property int $purchase_order_id
 * @property string $po_number
 * @property DateTimeInterface|string|null $order_date
 * @property DateTimeInterface|string|null $expected_delivery_date
 * @property string $status
 * @property int $supplier_id
 * @property string $supplier_name
 * @property int $warehouse_id
 * @property string $warehouse_code
 * @property string $warehouse_name
 * @property int $product_id
 * @property string $product_code
 * @property string $product_name
 * @property numeric-string|int|float $ordered_quantity
 * @property numeric-string|int|float $received_quantity
 * @property numeric-string|int|float $outstanding_quantity
 * @property int $receipt_count
 * @property DateTimeInterface|string|null $last_receipt_date
 * @property numeric-string|int|float $total_purchase_value
 */
class PurchaseHistoryReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->purchase_order_item_id,
            'purchase_order' => [
                'id' => $this->purchase_order_id,
                'po_number' => $this->po_number,
                'order_date' => $this->formatDate($this->order_date),
                'expected_delivery_date' => $this->formatDate($this->expected_delivery_date),
                'status' => $this->status,
            ],
            'supplier' => [
                'id' => $this->supplier_id,
                'name' => $this->supplier_name,
            ],
            'warehouse' => [
                'id' => $this->warehouse_id,
                'code' => $this->warehouse_code,
                'name' => $this->warehouse_name,
            ],
            'product' => [
                'id' => $this->product_id,
                'code' => $this->product_code,
                'name' => $this->product_name,
            ],
            'ordered_quantity' => (string) $this->ordered_quantity,
            'received_quantity' => (string) $this->received_quantity,
            'outstanding_quantity' => (string) $this->outstanding_quantity,
            'receipt_count' => $this->receipt_count,
            'last_receipt_date' => $this->formatDate($this->last_receipt_date),
            'total_purchase_value' => (string) $this->total_purchase_value,
        ];
    }

    private function formatDate(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return is_string($value) ? $value : null;
    }
}
