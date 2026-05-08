<?php

namespace App\Http\Requests\ApPayments;

use App\Http\Requests\BaseListingRequest;

abstract class AbstractApPaymentListingRequest extends BaseListingRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    protected function apPaymentListingRules(string $supplierKey, string $branchKey): array
    {
        return [
            'search' => ['nullable', 'string'],
            $supplierKey => ['nullable', 'integer', 'exists:suppliers,id'],
            $branchKey => ['nullable', 'integer', 'exists:branches,id'],
            'status' => [
                'nullable',
                'string',
                'in:draft,pending_approval,confirmed,reconciled,cancelled,void',
            ],
            'payment_method' => [
                'nullable',
                'string',
                'in:bank_transfer,cash,check,giro,other',
            ],
            'currency' => ['nullable', 'string', 'max:3'],
            'payment_date_from' => ['nullable', 'date'],
            'payment_date_to' => ['nullable', 'date', 'after_or_equal:payment_date_from'],
        ];
    }
}
