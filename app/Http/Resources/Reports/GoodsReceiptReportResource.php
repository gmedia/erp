<?php

namespace App\Http\Resources\Reports;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoodsReceiptReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $row */
        $row = (array) $this->resource;

        return [
            'goods_receipt' => [
                'id' => $row['goods_receipt_id'] ?? null,
                'gr_number' => $row['gr_number'] ?? null,
                'receipt_date' => $this->formatDate($row['receipt_date'] ?? null),
                'status' => $row['status'] ?? null,
            ],
            'purchase_order' => [
                'id' => $row['purchase_order_id'] ?? null,
                'po_number' => $row['po_number'] ?? null,
            ],
            'supplier' => [
                'id' => $row['supplier_id'] ?? null,
                'name' => $row['supplier_name'] ?? null,
            ],
            'warehouse' => [
                'id' => $row['warehouse_id'] ?? null,
                'code' => $row['warehouse_code'] ?? null,
                'name' => $row['warehouse_name'] ?? null,
            ],
            'item_count' => $row['item_count'] ?? null,
            'total_received_quantity' => (string) ($row['total_received_quantity'] ?? '0'),
            'total_accepted_quantity' => (string) ($row['total_accepted_quantity'] ?? '0'),
            'total_rejected_quantity' => (string) ($row['total_rejected_quantity'] ?? '0'),
            'total_receipt_value' => (string) ($row['total_receipt_value'] ?? '0'),
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
