<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryStocktakeVarianceReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stocktake' => [
                'id' => $this->inventory_stocktake_id,
                'stocktake_number' => $this->stocktake_number,
                'stocktake_date' => $this->stocktake_date?->toDateString(),
            ],
            'product' => [
                'id' => $this->product_id,
                'code' => $this->product_code,
                'name' => $this->product_name,
                'category' => [
                    'id' => $this->category_id,
                    'name' => $this->category_name,
                ],
            ],
            'warehouse' => [
                'id' => $this->warehouse_id,
                'code' => $this->warehouse_code,
                'name' => $this->warehouse_name,
                'branch' => [
                    'id' => $this->branch_id,
                    'name' => $this->branch_name,
                ],
            ],
            'system_quantity' => (string) $this->system_quantity,
            'counted_quantity' => (string) $this->counted_quantity,
            'variance' => (string) $this->variance,
            'result' => $this->result,
            'counted_at' => $this->counted_at?->toIso8601String(),
            'counted_by' => $this->counted_by_id ? [
                'id' => $this->counted_by_id,
                'name' => $this->counted_by_name,
            ] : null,
        ];
    }
}
