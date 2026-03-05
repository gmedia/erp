<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'product' => [
                'id' => $this->product_entity_id,
                'code' => $this->product_code,
                'name' => $this->product_name,
                'category' => [
                    'id' => $this->category_entity_id,
                    'name' => $this->category_name,
                ],
            ],
            'warehouse' => [
                'id' => $this->warehouse_entity_id,
                'code' => $this->warehouse_code,
                'name' => $this->warehouse_name,
                'branch' => [
                    'id' => $this->branch_entity_id,
                    'name' => $this->branch_name,
                ],
            ],
            'total_in' => (string) $this->total_in,
            'total_out' => (string) $this->total_out,
            'ending_balance' => (string) $this->ending_balance,
            'last_moved_at' => $this->last_moved_at?->toIso8601String(),
        ];
    }
}
