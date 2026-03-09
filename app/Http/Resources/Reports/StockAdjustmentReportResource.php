<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin mixed|\Illuminate\Database\Eloquent\Model
 */
class StockAdjustmentReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'adjustment_date' => $this->adjustment_date?->toDateString(),
            'adjustment_type' => $this->adjustment_type,
            'status' => $this->status,
            'warehouse' => [
                'id' => $this->warehouse_id,
                'code' => $this->warehouse_code,
                'name' => $this->warehouse_name,
                'branch' => [
                    'id' => $this->branch_id,
                    'name' => $this->branch_name,
                ],
            ],
            'adjustment_count' => (int) $this->adjustment_count,
            'total_quantity_adjusted' => (string) $this->total_quantity_adjusted,
            'total_adjustment_value' => (string) $this->total_adjustment_value,
        ];
    }
}
