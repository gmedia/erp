<?php

namespace App\Http\Requests\Employees;

use App\Http\Requests\BaseListingRequest;

class ExportEmployeeRequest extends BaseListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'department_id' => ['nullable', 'integer', 'exists:departments,id'],
                'position_id' => ['nullable', 'integer', 'exists:positions,id'],
                'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
                'employment_status' => ['nullable', 'string', 'in:regular,intern'],
            ],
            $this->listingSortRules(
                'id,employee_id,name,email,department_id,position_id,salary,employment_status,hire_date,created_at,updated_at'
            ),
        );
    }
}
