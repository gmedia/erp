<?php

namespace App\Http\Requests\StockTransfers;

class IndexStockTransferRequest extends AbstractStockTransferListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->stockTransferListingRules(
                'id,transfer_number,from_warehouse_id,to_warehouse_id,transfer_date,' .
                    'expected_arrival_date,status,created_at,updated_at',
                [
                    'expected_arrival_date_from' => ['nullable', 'date'],
                    'expected_arrival_date_to' => ['nullable', 'date'],
                ],
            ),
            $this->paginationRules(),
        );
    }
}
