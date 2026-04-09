<?php

namespace App\Http\Requests\Suppliers;

class StoreSupplierRequest extends AbstractSupplierRequest
{
    protected function usesSometimes(): bool
    {
        return false;
    }
}
