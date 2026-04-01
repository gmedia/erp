<?php

namespace App\Http\Requests\PipelineAuditTrail;

class IndexPipelineAuditTrailRequest extends AbstractPipelineAuditTrailListingRequest
{
    public function rules(): array
    {
        return $this->pipelineAuditTrailListingRules([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'export' => ['nullable', 'boolean'],
        ]);
    }
}
