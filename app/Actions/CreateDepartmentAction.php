<?php

namespace App\Actions;

use App\Models\Department;

class CreateDepartmentAction
{
    /**
     * Execute the action to create a new department.
     *
     * @param array{name: string} $data
     * @return Department
     */
    public function execute(array $data): Department
    {
        return Department::create($data);
    }
}
