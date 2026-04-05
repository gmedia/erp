<?php

namespace App\Http\Requests\Pipelines;

class IndexPipelineRequest extends AbstractPipelineListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->pipelineListingRules('id,name,code,entity_type,version,is_active,created_at,updated_at,created_by'),
            $this->paginationRules(),
        );
    }
}
