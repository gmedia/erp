<?php

namespace App\Http\Requests\Suppliers;

class UpdateSupplierRequest extends AbstractSupplierRequest
{
    protected function usesSometimes(): bool
    {
        return true;
    }
}
