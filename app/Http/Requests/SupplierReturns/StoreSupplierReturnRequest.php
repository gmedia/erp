<?php

namespace App\Http\Requests\SupplierReturns;

class StoreSupplierReturnRequest extends AbstractSupplierReturnMutationRequest
{
    protected function usesSometimes(): bool
    {
        return false;
    }
}
