<?php

namespace App\Http\Requests\StockTransfers;

use App\Http\Requests\BaseListingRequest;

abstract class AbstractStockTransferListingRequest extends BaseListingRequest
{
    /**
     * @param  array<string, array<int, string>>  $extraRules
     * @return array<string, array<int, string>>
     */
    protected function stockTransferListingRules(string $sortBy, array $extraRules = []): array
    {
        return [
            'search' => ['nullable', 'string'],
            'from_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'to_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'status' => ['nullable', 'string', 'in:draft,pending_approval,approved,in_transit,received,cancelled'],
            'transfer_date_from' => ['nullable', 'date'],
            'transfer_date_to' => ['nullable', 'date'],
            ...$extraRules,
            ...$this->listingSortRules($sortBy),
        ];
    }
}
