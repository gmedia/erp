<?php

namespace App\Http\Resources\Reports;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $goods_receipt_id
 * @property string $gr_number
 * @property \DateTimeInterface|string|null $receipt_date
 * @property string $status
 * @property int $purchase_order_id
 * @property string $po_number
 * @property int $supplier_id
 * @property string $supplier_name
 * @property int $warehouse_id
 * @property string $warehouse_code
 * @property string $warehouse_name
 * @property int $item_count
 * @property numeric-string|int|float $total_received_quantity
 * @property numeric-string|int|float $total_accepted_quantity
 * @property numeric-string|int|float $total_rejected_quantity
 * @property numeric-string|int|float $total_receipt_value
 */
class GoodsReceiptReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'goods_receipt' => [
                'id' => $this->goods_receipt_id,
                'gr_number' => $this->gr_number,
                'receipt_date' => $this->formatDate($this->receipt_date),
                'status' => $this->status,
            ],
            'purchase_order' => [
                'id' => $this->purchase_order_id,
                'po_number' => $this->po_number,
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
            'total_received_quantity' => (string) $this->total_received_quantity,
            'total_accepted_quantity' => (string) $this->total_accepted_quantity,
            'total_rejected_quantity' => (string) $this->total_rejected_quantity,
            'total_receipt_value' => (string) $this->total_receipt_value,
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
