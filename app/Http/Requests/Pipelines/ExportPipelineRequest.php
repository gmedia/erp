<?php

namespace App\Http\Requests\Pipelines;

use Illuminate\Foundation\Http\FormRequest;

class ExportPipelineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'entity_type' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'sort_by' => ['nullable', 'string', 'in:id,name,code,entity_type,version,is_active,created_at,updated_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            if ($this->is_active === '' || $this->is_active === null) {
                $this->merge(['is_active' => null]);
            } else {
                $this->merge([
                    'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
                ]);
            }
        }
    }
}
