<?php

namespace App\Http\Requests\Pipelines;

use Illuminate\Validation\Rule;

class StorePipelineStateRequest extends AbstractPipelineStateRequest
{
    protected function codeUniqueRule(): object
    {
        $pipeline = $this->route('pipeline');

        return Rule::unique('pipeline_states')->where('pipeline_id', $pipeline->id);
    }
}
