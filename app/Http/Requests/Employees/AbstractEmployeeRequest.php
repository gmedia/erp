<?php

namespace App\Http\Requests\Employees;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class AbstractEmployeeRequest extends FormRequest
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
            'employee_id' => $this->withSometimes(['required', 'string', $this->employeeIdUniqueRule()]),
            'name' => $this->withSometimes(['required', 'string', 'max:255']),
            'email' => $this->withSometimes(['required', 'email', $this->emailUniqueRule()]),
            'phone' => 'nullable|string|max:20',
            'department_id' => $this->withSometimes(['required', 'exists:departments,id']),
            'position_id' => $this->withSometimes(['required', 'exists:positions,id']),
            'branch_id' => $this->withSometimes(['required', 'exists:branches,id']),
            'salary' => 'nullable|numeric|min:0',
            'hire_date' => $this->withSometimes(['required', 'date']),
            'employment_status' => $this->withSometimes(['required', 'string', 'in:regular,intern']),
            'termination_date' => 'nullable|date',
        ];
    }

    /**
     * @param array<int, string|Rule> $rules
     * @return array<int, string|Rule>
     */
    private function withSometimes(array $rules): array
    {
        if (! $this->isUpdateRequest()) {
            return $rules;
        }

        return ['sometimes', ...$rules];
    }

    private function employeeIdUniqueRule(): string|Rule
    {
        if (! $this->isUpdateRequest()) {
            return 'unique:employees,employee_id';
        }

        return Rule::unique('employees', 'employee_id')->ignore($this->route('employee')->id);
    }

    private function emailUniqueRule(): string|Rule
    {
        if (! $this->isUpdateRequest()) {
            return 'unique:employees,email';
        }

        return Rule::unique('employees', 'email')->ignore($this->route('employee')->id);
    }

    private function isUpdateRequest(): bool
    {
        return $this instanceof UpdateEmployeeRequest;
    }
}