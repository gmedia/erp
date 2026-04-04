<?php

namespace App\Http\Requests\ApprovalFlows;

use App\Http\Requests\BaseListingRequest;

class ExportApprovalFlowRequest extends BaseListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'approvable_type' => ['nullable', 'string'],
                'is_active' => ['nullable', 'boolean'],
            ],
            $this->listingSortRules('name,code,approvable_type,is_active,created_at'),
        );
    }
}
