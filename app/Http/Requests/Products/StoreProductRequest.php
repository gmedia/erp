<?php

namespace App\Http\Requests\Products;

class StoreProductRequest extends AbstractProductRequest
{
    protected function usesSometimes(): bool
    {
        return false;
    }
}
