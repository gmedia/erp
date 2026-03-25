<?php

namespace App\Http\Resources\Reports;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string $po_number
 * @property \DateTimeInterface|string|null $order_date
 * @property \DateTimeInterface|string|null $expected_delivery_date
 * @property string $status
 * @property string $status_category
 * @property int $supplier_id
 * @property string $supplier_name
 * @property int $warehouse_id
 * @property string $warehouse_code
 * @property string $warehouse_name
 * @property int $item_count
 * @property numeric-string|int|float $ordered_quantity
 * @property numeric-string|int|float $received_quantity
 * @property numeric-string|int|float $outstanding_quantity
 * @property numeric-string|int|float $receipt_progress_percent
 * @property numeric-string|int|float $grand_total
 */
class PurchaseOrderStatusReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_order' => [
                'id' => $this->id,
                'po_number' => $this->po_number,
                'order_date' => $this->formatDate($this->order_date),
                'expected_delivery_date' => $this->formatDate($this->expected_delivery_date),
                'status' => $this->status,
                'status_category' => $this->status_category,
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
            'item_count' => $this->item_count,
            'ordered_quantity' => (string) $this->ordered_quantity,
            'received_quantity' => (string) $this->received_quantity,
            'outstanding_quantity' => (string) $this->outstanding_quantity,
            'receipt_progress_percent' => (string) $this->receipt_progress_percent,
            'grand_total' => (string) $this->grand_total,
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
