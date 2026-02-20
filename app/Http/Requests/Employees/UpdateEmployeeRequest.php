<?php

namespace App\Http\Requests\Employees;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property \App\Models\Employee $employee
 *
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
            'employee_id' => [
                'sometimes',
                'required',
                'string',
                Rule::unique('employees', 'employee_id')->ignore($this->route('employee')->id),
            ],
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('employees', 'email')->ignore($this->route('employee')->id),
            ],
            'phone' => 'nullable|string|max:20',
            'department_id' => 'sometimes|required|exists:departments,id',
            'position_id' => 'sometimes|required|exists:positions,id',
            'branch_id' => 'sometimes|required|exists:branches,id',
            'salary' => 'nullable|numeric|min:0',
            'hire_date' => 'sometimes|required|date',
            'employment_status' => 'sometimes|required|string|in:regular,intern',
            'termination_date' => 'nullable|date',
        ];
    }
}
