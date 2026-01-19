<?php

namespace App\Http\Requests\Employees;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email',
            'phone' => 'nullable|string|max:20',
            'department' => 'required|exists:departments,id',
            'position' => 'required|exists:positions,id',
            'salary' => 'required|numeric|min:0',
            'hire_date' => 'required|date',
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

        return $validated;
    }
}
