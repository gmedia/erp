<?php

namespace App\Http\Requests\ApprovalFlows;

use App\Http\Requests\BaseListingRequest;

abstract class AbstractApprovalFlowListingRequest extends BaseListingRequest
{
    protected function approvalFlowListingRules(string $sortBy): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'approvable_type' => ['nullable', 'string'],
                'is_active' => ['nullable', 'boolean'],
            ],
            $this->listingSortRules($sortBy),
        );
    }
}
