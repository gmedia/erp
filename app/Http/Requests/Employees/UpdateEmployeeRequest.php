<?php

namespace App\Http\Requests\Employees;

/**
 * @property \App\Models\Employee $employee
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
