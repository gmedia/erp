<?php

namespace App\Http\Requests\ArReceipts;

use App\Http\Requests\BaseListingRequest;

abstract class AbstractArReceiptListingRequest extends BaseListingRequest
{
    protected function arReceiptListingRules(string $customerKey, string $branchKey): array
    {
        return [
            'search' => ['nullable', 'string'],
            $customerKey => ['nullable', 'integer', 'exists:customers,id'],
            $branchKey => ['nullable', 'integer', 'exists:branches,id'],
            'status' => [
                'nullable',
                'string',
                'in:draft,confirmed,reconciled,cancelled,void',
            ],
            'payment_method' => [
                'nullable',
                'string',
                'in:bank_transfer,cash,check,giro,credit_card,other',
            ],
            'currency' => ['nullable', 'string', 'max:3'],
            'receipt_date_from' => ['nullable', 'date'],
            'receipt_date_to' => ['nullable', 'date', 'after_or_equal:receipt_date_from'],
        ];
    }
}
