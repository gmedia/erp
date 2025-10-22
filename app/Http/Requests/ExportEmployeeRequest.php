<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportEmployeeRequest extends FormRequest
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
            'department' => ['nullable', 'string', 'in:Engineering,Marketing,Sales,HR,Finance'],
            'position' => ['nullable', 'string', 'in:Engineering,Marketing,Sales,HR,Finance'],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'min:0'],
            'hire_date_from' => ['nullable', 'date'],
            'hire_date_to' => ['nullable', 'date'],
            'sort_by' => ['nullable', 'string', 'in:name,email,department,position,salary,hire_date,created_at,updated_at'],
            'sort_direction' => ['nullable', 'in:asc,desc'],
        ];
    }
}
