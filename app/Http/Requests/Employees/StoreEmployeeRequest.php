<?php

namespace App\Http\Requests\Employees;

class StoreEmployeeRequest extends AbstractEmployeeRequest
{
    protected function usesSometimes(): bool
    {
        return false;
    }
}
