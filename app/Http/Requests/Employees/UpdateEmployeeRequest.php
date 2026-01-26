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
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                Rule::unique('employees', 'email')->ignore($this->route('employee')->id),
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'department' => ['sometimes', 'exists:departments,id'],
            'position' => ['sometimes', 'exists:positions,id'],
            'branch' => ['sometimes', 'exists:branches,id'],
            'salary' => ['sometimes', 'numeric', 'min:0'],
            'hire_date' => ['sometimes', 'date'],
        ];
    }

    /**
     * Get the validated data and map department/position to FK columns.
     */
    public function validated($key = null, $default = null): mixed
    {
        $validated = parent::validated($key, $default);

        if ($key !== null) {
            return $validated;
        }

        // Map department to department_id and position to position_id
        if (isset($validated['department'])) {
            $validated['department_id'] = $validated['department'];
            unset($validated['department']);
        }

        if (isset($validated['position'])) {
            $validated['position_id'] = $validated['position'];
            unset($validated['position']);
        }

        if (isset($validated['branch'])) {
            $validated['branch_id'] = $validated['branch'];
            unset($validated['branch']);
        }

        return $validated;
    }
}
