<?php

namespace App\Http\Requests\StockTransfers;

class ExportStockTransferRequest extends AbstractStockTransferListingRequest
{
    public function rules(): array
    {
        return $this->stockTransferListingRules(
            'id,transfer_number,from_warehouse_id,to_warehouse_id,transfer_date,expected_arrival_date,status,created_at,updated_at'
        );
    }
}
