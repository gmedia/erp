<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderStatusReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_order' => [
                'id' => $this->id,
                'po_number' => $this->po_number,
                'order_date' => $this->order_date?->toDateString(),
                'expected_delivery_date' => $this->expected_delivery_date?->toDateString(),
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
}
