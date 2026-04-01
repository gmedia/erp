<?php

namespace App\Http\Requests\ApprovalAuditTrail;

use App\Http\Requests\BaseListingRequest;

abstract class AbstractApprovalAuditTrailListingRequest extends BaseListingRequest
{
    protected function approvalAuditTrailListingRules(array $extraRules = []): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'approvable_type' => ['nullable', 'string', 'max:255'],
            'event' => ['nullable', 'string', 'max:255'],
            'actor_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            ...$this->listingSortRules('id,approvable_type,approvable_id,event,actor_user_id,created_at'),
            ...$extraRules,
        ];
    }
}