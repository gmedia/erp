<?php

namespace App\Http\Requests\Pipelines;

class ExportPipelineRequest extends AbstractPipelineListingRequest
{
    public function rules(): array
    {
        return $this->pipelineListingRules('id,name,code,entity_type,version,is_active,created_at,updated_at');
    }
}
