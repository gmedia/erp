<?php

namespace App\Http\Requests\Departments;

use Illuminate\Foundation\Http\FormRequest;

class ExportDepartmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'sort_by' => ['nullable', 'string', 'in:id,name,created_at,updated_at'],
            'sort_direction' => ['nullable', 'in:asc,desc'],
        ];
    }
}
