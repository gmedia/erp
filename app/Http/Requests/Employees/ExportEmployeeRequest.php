<?php

namespace App\Http\Requests\Employees;

class ExportEmployeeRequest extends AbstractEmployeeListingRequest
{
    public function rules(): array
    {
        return $this->employeeListingRules(
            'id,employee_id,name,email,employments.department_id,employments.position_id,'
                . 'employments.salary,employments.employment_status,employments.hire_date,created_at,updated_at',
            includeIntegerRules: true,
        );
    }
}
