<?php

namespace App\Http\Requests\StockMovements;

class ExportStockMovementRequest extends AbstractStockMovementListingRequest
{
    public function rules(): array
    {
        return $this->stockMovementListingRules([
            'format' => ['nullable', 'string', 'in:xlsx,csv'],
        ]);
    }
}
