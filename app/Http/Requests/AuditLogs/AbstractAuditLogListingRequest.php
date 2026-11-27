<?php

namespace App\Http\Requests\AuditLogs;

use App\Http\Requests\BaseListingRequest;
use Illuminate\Validation\Rule;

abstract class AbstractAuditLogListingRequest extends BaseListingRequest
{
    protected function auditLogListingRules(array $extraRules = []): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'log_name' => ['nullable', 'string', 'max:100'],
            'event' => ['nullable', 'string', Rule::in(['created', 'updated', 'deleted'])],
            'subject_type' => ['nullable', 'string', 'max:255'],
            'causer_id' => ['nullable', 'integer', 'exists:users,id'],
            'subject_id' => ['nullable', 'integer'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            ...$this->listingSortRules('id,event,causer_id,subject_type,created_at'),
            ...$extraRules,
        ];
    }
}
