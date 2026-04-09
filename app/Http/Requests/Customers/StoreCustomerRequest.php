<?php

namespace App\Http\Requests\Customers;

class StoreCustomerRequest extends AbstractCustomerRequest
{
    protected function usesSometimes(): bool
    {
        return false;
    }
}
