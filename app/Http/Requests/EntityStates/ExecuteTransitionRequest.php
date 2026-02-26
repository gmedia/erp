<?php

namespace App\Http\Requests\EntityStates;

use Illuminate\Foundation\Http\FormRequest;

class ExecuteTransitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization is handled in the action based on transition config
    }

    public function rules(): array
    {
        return [
            'transition_id' => ['required', 'integer', 'exists:pipeline_transitions,id'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
