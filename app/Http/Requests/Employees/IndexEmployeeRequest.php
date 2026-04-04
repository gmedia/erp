<?php

namespace App\Http\Requests\Employees;

use App\Http\Requests\BaseListingRequest;

class IndexEmployeeRequest extends BaseListingRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'department_id' => ['nullable', 'exists:departments,id'],
                'position_id' => ['nullable', 'exists:positions,id'],
                'branch_id' => ['nullable', 'exists:branches,id'],
                'employment_status' => ['nullable', 'string', 'in:regular,intern'],
                'salary_min' => ['nullable', 'numeric', 'min:0'],
                'salary_max' => ['nullable', 'numeric', 'min:0'],
                'hire_date_from' => ['nullable', 'date'],
                'hire_date_to' => ['nullable', 'date'],
            ],
            $this->listingSortRules(
                'id,employee_id,name,email,phone,department_id,position_id,' .
                    'branch_id,salary,employment_status,hire_date,created_at,updated_at'
            ),
            $this->paginationRules(),
        );
    }
}
