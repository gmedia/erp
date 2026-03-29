<?php

namespace App\Http\Requests\Pipelines;

class UpdatePipelineTransitionRequest extends AbstractPipelineTransitionRequest
{
    protected function validateActionId(): bool
    {
        return true;
    }
}
