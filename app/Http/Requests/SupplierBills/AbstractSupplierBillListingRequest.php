<?php

namespace App\Http\Requests\SupplierBills;

use App\Http\Requests\BaseListingRequest;

abstract class AbstractSupplierBillListingRequest extends BaseListingRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    protected function supplierBillListingRules(string $supplierKey, string $branchKey): array
    {
        return [
            'search' => ['nullable', 'string'],
            $supplierKey => ['nullable', 'integer', 'exists:suppliers,id'],
            $branchKey => ['nullable', 'integer', 'exists:branches,id'],
            'status' => [
                'nullable',
                'string',
                'in:draft,confirmed,partially_paid,paid,overdue,cancelled,void',
            ],
            'currency' => ['nullable', 'string', 'max:3'],
            'bill_date_from' => ['nullable', 'date'],
            'bill_date_to' => ['nullable', 'date', 'after_or_equal:bill_date_from'],
            'due_date_from' => ['nullable', 'date'],
            'due_date_to' => ['nullable', 'date', 'after_or_equal:due_date_from'],
        ];
    }
}
