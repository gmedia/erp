<?php

namespace App\Http\Requests\EntityStates;

use App\Http\Requests\AuthorizedFormRequest;

class ExecuteTransitionRequest extends AuthorizedFormRequest
{
    public function rules(): array
    {
        return [
            'transition_id' => ['required', 'integer', 'exists:pipeline_transitions,id'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
