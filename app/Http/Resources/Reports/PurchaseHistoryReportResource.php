<?php

namespace App\Http\Resources\Reports;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseHistoryReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $row */
        $row = (array) $this->resource;

        return [
            'id' => $row['purchase_order_item_id'] ?? null,
            'purchase_order' => [
                'id' => $row['purchase_order_id'] ?? null,
                'po_number' => $row['po_number'] ?? null,
                'order_date' => $this->formatDate($row['order_date'] ?? null),
                'expected_delivery_date' => $this->formatDate($row['expected_delivery_date'] ?? null),
                'status' => $row['status'] ?? null,
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
            'product' => [
                'id' => $row['product_id'] ?? null,
                'code' => $row['product_code'] ?? null,
                'name' => $row['product_name'] ?? null,
            ],
            'ordered_quantity' => (string) ($row['ordered_quantity'] ?? '0'),
            'received_quantity' => (string) ($row['received_quantity'] ?? '0'),
            'outstanding_quantity' => (string) ($row['outstanding_quantity'] ?? '0'),
            'receipt_count' => $row['receipt_count'] ?? null,
            'last_receipt_date' => $this->formatDate($row['last_receipt_date'] ?? null),
            'total_purchase_value' => (string) ($row['total_purchase_value'] ?? '0'),
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
