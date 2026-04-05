<?php

namespace App\Http\Requests\Employees;

class IndexEmployeeRequest extends AbstractEmployeeListingRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return array_merge(
            $this->employeeListingRules(
                'id,employee_id,name,email,phone,department_id,position_id,' .
                    'branch_id,salary,employment_status,hire_date,created_at,updated_at',
                [
                    'salary_min' => ['nullable', 'numeric', 'min:0'],
                    'salary_max' => ['nullable', 'numeric', 'min:0'],
                    'hire_date_from' => ['nullable', 'date'],
                    'hire_date_to' => ['nullable', 'date'],
                ],
            ),
            $this->paginationRules(),
        );
    }
}
