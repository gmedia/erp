<?php

namespace App\Http\Requests\Customers;

class UpdateCustomerRequest extends AbstractCustomerRequest
{
    protected function usesSometimes(): bool
    {
        return true;
    }
}
