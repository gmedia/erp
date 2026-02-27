<?php

namespace App\Http\Requests\Pipelines;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePipelineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('pipelines')->ignore($this->pipeline)],
            'entity_type' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'version' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
            'conditions' => ['sometimes', 'nullable', 'json'],
        ];
    }
}
