<?php

namespace App\Actions;

use App\DTOs\StoreEmployeeData;
use App\Models\Employee;

class CreateEmployeeAction
{
    /**
     * Execute the action to create a new employee.
     *
     * @param StoreEmployeeData $data
     * @return Employee
     */
    public function execute(StoreEmployeeData $data): Employee
    {
        return Employee::create($data->toArray());
    }
}
