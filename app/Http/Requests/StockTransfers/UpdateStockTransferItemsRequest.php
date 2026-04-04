<?php

namespace App\Http\Requests\StockTransfers;

use App\Http\Requests\AbstractProductUnitItemsUpdateRequest;

class UpdateStockTransferItemsRequest extends AbstractProductUnitItemsUpdateRequest
{
    protected function additionalItemRules(): array
    {
        return [
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.quantity_received' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
