<?php

namespace App\Http\Requests\Pipelines;

use Illuminate\Validation\Rule;

class StorePipelineRequest extends AbstractPipelineRequest
{
    protected function pipelineCodeUniqueRule(): Rule|string
    {
        return 'unique:pipelines,code';
    }
}
