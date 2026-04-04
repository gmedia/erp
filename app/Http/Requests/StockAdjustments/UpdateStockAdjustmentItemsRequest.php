<?php

namespace App\Http\Requests\StockAdjustments;

use App\Http\Requests\AbstractProductUnitItemsUpdateRequest;

class UpdateStockAdjustmentItemsRequest extends AbstractProductUnitItemsUpdateRequest
{
    protected function additionalItemRules(): array
    {
        return [
            'items.*.quantity_before' => ['nullable', 'numeric', 'min:0'],
            'items.*.quantity_adjusted' => ['required', 'numeric', 'not_in:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.reason' => ['nullable', 'string'],
        ];
    }
}
