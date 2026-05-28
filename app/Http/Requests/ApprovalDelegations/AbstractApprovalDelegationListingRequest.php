<?php

namespace App\Http\Requests\ApprovalDelegations;

use App\Http\Requests\BaseListingRequest;
use Illuminate\Contracts\Validation\ValidationRule;

abstract class AbstractApprovalDelegationListingRequest extends BaseListingRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function approvalDelegationBaseRules(string $delegatorKey, string $delegateKey): array
    {
        return [
            'search' => ['nullable', 'string'],
            $delegatorKey => ['nullable', 'exists:users,id'],
            $delegateKey => ['nullable', 'exists:users,id'],
            'is_active' => ['nullable', 'string', 'in:true,false,1,0'],
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function approvalDelegationSortRules(string $sortBy): array
    {
        return $this->listingSortRules($sortBy);
    }
}
