<?php

namespace App\Http\Requests\PipelineAuditTrail;

use App\Http\Requests\BaseListingRequest;

abstract class AbstractPipelineAuditTrailListingRequest extends BaseListingRequest
{
    protected function pipelineAuditTrailListingRules(array $extraRules = []): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'entity_type' => ['nullable', 'string'],
            'pipeline_id' => ['nullable', 'integer', 'exists:pipelines,id'],
            'from_state_id' => ['nullable', 'integer', 'exists:pipeline_states,id'],
            'to_state_id' => ['nullable', 'integer', 'exists:pipeline_states,id'],
            'performed_by' => ['nullable', 'integer', 'exists:users,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            ...$this->listingSortRules('created_at,entity_type,performed_by'),
            ...$extraRules,
        ];
    }
}