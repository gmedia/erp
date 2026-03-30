<?php

namespace App\Http\Requests\ApprovalDelegations;

use Illuminate\Foundation\Http\FormRequest;

abstract class AbstractApprovalDelegationListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    protected function approvalDelegationListingRules(string $delegatorKey, string $delegateKey, string $sortBy): array
    {
        return [
            'search' => ['nullable', 'string'],
            $delegatorKey => ['nullable', 'exists:users,id'],
            $delegateKey => ['nullable', 'exists:users,id'],
            'is_active' => ['nullable', 'string', 'in:true,false,1,0'],
            'sort_by' => ['nullable', 'string', 'in:' . $sortBy],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
