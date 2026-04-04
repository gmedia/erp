<?php

namespace App\Http\Requests\InventoryStocktakes;

use App\Http\Requests\AbstractProductUnitItemsUpdateRequest;

class UpdateInventoryStocktakeItemsRequest extends AbstractProductUnitItemsUpdateRequest
{
    protected function additionalItemRules(): array
    {
        return [
            'items.*.system_quantity' => ['required', 'numeric', 'min:0'],
            'items.*.counted_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
