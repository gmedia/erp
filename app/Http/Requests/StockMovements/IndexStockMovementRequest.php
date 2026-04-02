<?php

namespace App\Http\Requests\StockMovements;

class IndexStockMovementRequest extends AbstractStockMovementListingRequest
{
    public function rules(): array
    {
        return $this->stockMovementListingRules([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'export' => ['nullable', 'boolean'],
        ]);
    }
}
