<?php

namespace App\Http\Requests\Employees;

class ExportEmployeeRequest extends AbstractEmployeeListingRequest
{
    public function rules(): array
    {
        return $this->employeeListingRules(
            'id,employee_id,name,email,department_id,position_id,salary,employment_status,hire_date,created_at,updated_at',
            includeIntegerRules: true,
        );
    }
}
