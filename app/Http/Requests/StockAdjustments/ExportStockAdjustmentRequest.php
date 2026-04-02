<?php

namespace App\Http\Requests\StockAdjustments;

class ExportStockAdjustmentRequest extends AbstractStockAdjustmentListingRequest
{
    public function rules(): array
    {
        return $this->stockAdjustmentListingRules(
            'id,adjustment_number,warehouse_id,adjustment_date,adjustment_type,status,inventory_stocktake_id,'
                . 'created_at,updated_at'
        );
    }
}
