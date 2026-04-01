<?php

namespace App\Http\Requests\SupplierReturns;

class UpdateSupplierReturnRequest extends AbstractSupplierReturnMutationRequest
{
    protected function usesSometimes(): bool
    {
        return true;
    }
}
