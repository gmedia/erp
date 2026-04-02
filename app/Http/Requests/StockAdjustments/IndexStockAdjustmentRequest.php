<?php

namespace App\Http\Requests\StockAdjustments;

class IndexStockAdjustmentRequest extends AbstractStockAdjustmentListingRequest
{
    public function rules(): array
    {
        return $this->stockAdjustmentListingRules(
            'id,adjustment_number,warehouse_id,adjustment_date,adjustment_type,status,inventory_stocktake_id,'
                . 'journal_entry_id,approved_by,approved_at,created_at,updated_at',
            [
                'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
                'page' => ['nullable', 'integer', 'min:1'],
            ]
        );
    }
}
