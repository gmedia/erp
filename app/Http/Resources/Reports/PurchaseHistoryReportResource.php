<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseHistoryReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->purchase_order_item_id,
            'purchase_order' => [
                'id' => $this->purchase_order_id,
                'po_number' => $this->po_number,
                'order_date' => $this->order_date?->toDateString(),
                'expected_delivery_date' => $this->expected_delivery_date?->toDateString(),
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
            'last_receipt_date' => $this->last_receipt_date?->toDateString(),
            'total_purchase_value' => (string) $this->total_purchase_value,
        ];
    }
}
