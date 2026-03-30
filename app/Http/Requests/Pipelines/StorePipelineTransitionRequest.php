<?php

namespace App\Http\Requests\Pipelines;

class StorePipelineTransitionRequest extends AbstractPipelineTransitionRequest
{
    protected function validateActionId(): bool
    {
        return false;
    }
}
