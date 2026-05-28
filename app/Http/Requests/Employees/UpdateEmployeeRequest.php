<?php

namespace App\Http\Requests\Employees;

use App\Models\Employee;

/**
 * @property Employee $employee
 *
 * @method \Illuminate\Routing\Route route($param = null)
 */
class UpdateEmployeeRequest extends AbstractEmployeeRequest
{
    protected function usesSometimes(): bool
    {
        return true;
    }
}
