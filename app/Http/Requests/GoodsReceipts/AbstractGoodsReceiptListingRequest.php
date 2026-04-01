<?php

namespace App\Http\Requests\GoodsReceipts;

use App\Http\Requests\BaseListingRequest;

abstract class AbstractGoodsReceiptListingRequest extends BaseListingRequest
{
    /**
     * @param  array<string, array<int, string>>  $extraRules
     * @return array<string, array<int, string>>
     */
    protected function goodsReceiptListingRules(
        string $sortBy,
        string $purchaseOrderField,
        string $warehouseField,
        array $extraRules = []
    ): array {
        return [
            'search' => ['nullable', 'string'],
            $purchaseOrderField => ['nullable', 'integer', 'exists:purchase_orders,id'],
            $warehouseField => ['nullable', 'integer', 'exists:warehouses,id'],
            'status' => ['nullable', 'string', 'in:draft,confirmed,cancelled'],
            'received_by' => ['nullable', 'integer', 'exists:employees,id'],
            'receipt_date_from' => ['nullable', 'date'],
            'receipt_date_to' => ['nullable', 'date', 'after_or_equal:receipt_date_from'],
            ...$this->listingSortRules($sortBy),
            ...$extraRules,
        ];
    }
}
