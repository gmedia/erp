<?php

namespace App\Http\Requests\CustomerInvoices;

use App\Http\Requests\BaseListingRequest;

abstract class AbstractCustomerInvoiceListingRequest extends BaseListingRequest
{
    protected function customerInvoiceListingRules(string $customerKey, string $branchKey): array
    {
        return [
            'search' => ['nullable', 'string'],
            $customerKey => ['nullable', 'integer', 'exists:customers,id'],
            $branchKey => ['nullable', 'integer', 'exists:branches,id'],
            'status' => [
                'nullable',
                'string',
                'in:draft,sent,partially_paid,paid,overdue,cancelled,void',
            ],
            'currency' => ['nullable', 'string', 'max:3'],
            'invoice_date_from' => ['nullable', 'date'],
            'invoice_date_to' => ['nullable', 'date', 'after_or_equal:invoice_date_from'],
            'due_date_from' => ['nullable', 'date'],
            'due_date_to' => ['nullable', 'date', 'after_or_equal:due_date_from'],
        ];
    }
}
