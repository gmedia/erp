<?php

namespace App\Http\Requests\PipelineAuditTrail;

class ExportPipelineAuditTrailRequest extends AbstractPipelineAuditTrailListingRequest
{
    public function rules(): array
    {
        return $this->pipelineAuditTrailListingRules([
            'format' => ['nullable', 'string', 'in:xlsx,csv'],
        ]);
    }
}
