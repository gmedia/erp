<?php

namespace App\Actions\Employees;

use App\DTOs\Employees\UpdateEmployeeData;
use App\Models\Employee;

class UpdateEmployeeAction
{
    /**
     * Execute the action to update an existing employee.
     */
    public function execute(Employee $employee, UpdateEmployeeData $data): Employee
    {
        $employee->update($data->toArray());

        return $employee->fresh();
    }
}
