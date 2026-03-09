<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\StockMovement
 * @property int $product_entity_id
 * @property string $product_code
 * @property string $product_name
 * @property int $category_entity_id
 * @property string $category_name
 * @property int $warehouse_entity_id
 * @property string $warehouse_code
 * @property string $warehouse_name
 * @property int $branch_entity_id
 * @property string $branch_name
 * @property float $total_in
 * @property float $total_out
 * @property float $ending_balance
 * @property \Illuminate\Support\Carbon|null $last_moved_at
 */
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
