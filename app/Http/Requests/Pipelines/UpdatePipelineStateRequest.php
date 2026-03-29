<?php

namespace App\Http\Requests\Pipelines;

use Illuminate\Validation\Rule;

class UpdatePipelineStateRequest extends AbstractPipelineStateRequest
{
    protected function codeUniqueRule(): object
    {
        $pipeline = $this->route('pipeline');
        $state = $this->route('state');

        return Rule::unique('pipeline_states')
            ->where('pipeline_id', $pipeline->id)
            ->ignore($state->id);
    }
}
