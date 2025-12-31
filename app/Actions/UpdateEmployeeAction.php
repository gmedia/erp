<?php

namespace App\Actions;

use App\DTOs\UpdateEmployeeData;
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
