<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoodsReceiptReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'goods_receipt' => [
                'id' => $this->goods_receipt_id,
                'gr_number' => $this->gr_number,
                'receipt_date' => $this->receipt_date?->toDateString(),
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
}
