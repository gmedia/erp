<?php

namespace App\Http\Resources\Reports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin mixed|Model
 */
class StockAdjustmentReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'stock_adjustment_id' => $this->stock_adjustment_id,
            'adjustment_number' => $this->adjustment_number,
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
            'total_increase_quantity' => (string) $this->total_increase_quantity,
            'total_decrease_quantity' => (string) $this->total_decrease_quantity,
            'total_adjustment_value' => (string) $this->total_adjustment_value,
        ];
    }
}
