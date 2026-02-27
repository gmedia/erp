<?php

namespace App\Http\Requests\Pipelines;

use Illuminate\Foundation\Http\FormRequest;

class StorePipelineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', 'unique:pipelines,code'],
            'entity_type' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'version' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
            'conditions' => ['nullable', 'json'],
        ];
    }
}
