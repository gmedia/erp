<?php

namespace App\Http\Requests\PurchaseRequests;

use Illuminate\Foundation\Http\FormRequest;

abstract class AbstractPurchaseRequestListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function purchaseRequestListingRules(string $branchKey, string $departmentKey, string $sortBy): array
    {
        return [
            'search' => ['nullable', 'string'],
            $branchKey => ['nullable', 'integer', 'exists:branches,id'],
            $departmentKey => ['nullable', 'integer', 'exists:departments,id'],
            'requested_by' => ['nullable', 'integer', 'exists:employees,id'],
            'priority' => ['nullable', 'string', 'in:low,normal,high,urgent'],
            'status' => [
                'nullable',
                'string',
                'in:draft,pending_approval,approved,rejected,partially_ordered,fully_ordered,cancelled',
            ],
            'request_date_from' => ['nullable', 'date'],
            'request_date_to' => ['nullable', 'date', 'after_or_equal:request_date_from'],
            'required_date_from' => ['nullable', 'date'],
            'required_date_to' => ['nullable', 'date', 'after_or_equal:required_date_from'],
            'sort_by' => ['nullable', 'string', 'in:' . $sortBy],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
