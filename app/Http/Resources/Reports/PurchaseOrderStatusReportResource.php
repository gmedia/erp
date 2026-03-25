<?php

namespace App\Http\Resources\Reports;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderStatusReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $row */
        $row = (array) $this->resource;

        return [
            'id' => $row['id'] ?? null,
            'purchase_order' => [
                'id' => $row['id'] ?? null,
                'po_number' => $row['po_number'] ?? null,
                'order_date' => $this->formatDate($row['order_date'] ?? null),
                'expected_delivery_date' => $this->formatDate($row['expected_delivery_date'] ?? null),
                'status' => $row['status'] ?? null,
                'status_category' => $row['status_category'] ?? null,
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
            'ordered_quantity' => (string) ($row['ordered_quantity'] ?? '0'),
            'received_quantity' => (string) ($row['received_quantity'] ?? '0'),
            'outstanding_quantity' => (string) ($row['outstanding_quantity'] ?? '0'),
            'receipt_progress_percent' => (string) ($row['receipt_progress_percent'] ?? '0'),
            'grand_total' => (string) ($row['grand_total'] ?? '0'),
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
