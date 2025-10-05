<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property \App\Models\Employee $employee
 * @method \Illuminate\Routing\Route route($param = null)
 */

class UpdateEmployeeRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                Rule::unique('employees', 'email')->ignore($this->route('employee')->id),
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'department' => ['sometimes', 'string', 'in:Engineering,Marketing,Sales,HR,Finance'],
            'position' => ['sometimes', 'string', 'max:255'],
            'salary' => ['sometimes', 'numeric', 'min:0'],
            'hire_date' => ['sometimes', 'date'],
        ];
    }
}
