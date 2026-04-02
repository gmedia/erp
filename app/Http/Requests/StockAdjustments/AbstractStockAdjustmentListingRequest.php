<?php

namespace App\Http\Requests\StockAdjustments;

use App\Http\Requests\BaseListingRequest;

abstract class AbstractStockAdjustmentListingRequest extends BaseListingRequest
{
    /**
     * @param  array<string, array<int, string>>  $extraRules
     * @return array<string, array<int, string>>
     */
    protected function stockAdjustmentListingRules(string $sortBy, array $extraRules = []): array
    {
        return [
            'search' => ['nullable', 'string'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'status' => ['nullable', 'string', 'in:draft,pending_approval,approved,cancelled'],
            'adjustment_type' => [
                'nullable',
                'string',
                'in:damage,expired,shrinkage,correction,stocktake_result,initial_stock,other',
            ],
            'inventory_stocktake_id' => ['nullable', 'exists:inventory_stocktakes,id'],
            'adjustment_date_from' => ['nullable', 'date'],
            'adjustment_date_to' => ['nullable', 'date'],
            ...$this->listingSortRules($sortBy),
            ...$extraRules,
        ];
    }
}
