<?php

namespace App\Http\Requests\PurchaseOrders;

use App\Http\Requests\BaseListingRequest;

abstract class AbstractPurchaseOrderListingRequest extends BaseListingRequest
{
    protected function prepareForValidation(): void
    {
        // Bentuk tunggal (?status=draft) dinormalkan jadi daftar agar kontrak lama tetap berlaku.
        if (is_string($this->input('status')) && $this->input('status') !== '') {
            $this->merge(['status' => [$this->input('status')]]);
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function purchaseOrderListingRules(string $supplierKey, string $warehouseKey): array
    {
        return [
            'search' => ['nullable', 'string'],
            $supplierKey => ['nullable', 'integer', 'exists:suppliers,id'],
            $warehouseKey => ['nullable', 'integer', 'exists:warehouses,id'],
            'status' => ['nullable', 'array'],
            'status.*' => [
                'string',
                'in:draft,pending_approval,confirmed,rejected,partially_received,fully_received,cancelled,closed',
            ],
            'currency' => ['nullable', 'string', 'max:3'],
            'order_date_from' => ['nullable', 'date'],
            'order_date_to' => ['nullable', 'date', 'after_or_equal:order_date_from'],
        ];
    }
}
