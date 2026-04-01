<?php

namespace App\Http\Requests\SupplierReturns;

use App\Http\Requests\BaseListingRequest;

abstract class AbstractSupplierReturnListingRequest extends BaseListingRequest
{
    /**
     * @param  array<string, array<int, string>>  $extraRules
     * @return array<string, array<int, string>>
     */
    protected function supplierReturnListingRules(
        string $sortBy,
        string $purchaseOrderField,
        string $goodsReceiptField,
        string $supplierField,
        string $warehouseField,
        array $extraRules = []
    ): array {
        return [
            'search' => ['nullable', 'string'],
            $purchaseOrderField => ['nullable', 'integer', 'exists:purchase_orders,id'],
            $goodsReceiptField => ['nullable', 'integer', 'exists:goods_receipts,id'],
            $supplierField => ['nullable', 'integer', 'exists:suppliers,id'],
            $warehouseField => ['nullable', 'integer', 'exists:warehouses,id'],
            'reason' => ['nullable', 'string', 'in:defective,wrong_item,excess_quantity,damaged,other'],
            'status' => ['nullable', 'string', 'in:draft,confirmed,cancelled'],
            'return_date_from' => ['nullable', 'date'],
            'return_date_to' => ['nullable', 'date', 'after_or_equal:return_date_from'],
            ...$this->listingSortRules($sortBy),
            ...$extraRules,
        ];
    }
}
