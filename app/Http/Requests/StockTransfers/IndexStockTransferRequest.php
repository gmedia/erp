<?php

namespace App\Http\Requests\StockTransfers;

use App\Http\Requests\BaseListingRequest;

class IndexStockTransferRequest extends BaseListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'from_warehouse_id' => ['nullable', 'exists:warehouses,id'],
                'to_warehouse_id' => ['nullable', 'exists:warehouses,id'],
                'status' => ['nullable', 'string', 'in:draft,pending_approval,approved,in_transit,received,cancelled'],
                'transfer_date_from' => ['nullable', 'date'],
                'transfer_date_to' => ['nullable', 'date'],
                'expected_arrival_date_from' => ['nullable', 'date'],
                'expected_arrival_date_to' => ['nullable', 'date'],
            ],
            $this->listingSortRules(
                'id,transfer_number,from_warehouse_id,to_warehouse_id,transfer_date,' .
                    'expected_arrival_date,status,created_at,updated_at'
            ),
            $this->paginationRules(),
        );
    }
}
