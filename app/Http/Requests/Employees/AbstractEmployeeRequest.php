<?php

namespace App\Http\Requests\Employees;

use App\Http\Requests\Concerns\HasSometimesArrayRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

abstract class AbstractEmployeeRequest extends FormRequest
{
    use HasSometimesArrayRules;

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

    protected function usesSometimes(): bool
    {
        return $this->isUpdateRequest();
    }

    private function employeeIdUniqueRule(): string|Unique
    {
        if (! $this->isUpdateRequest()) {
            return 'unique:employees,employee_id';
        }

        return Rule::unique('employees', 'employee_id')->ignore($this->route('employee')->id);
    }

    private function emailUniqueRule(): string|Unique
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
