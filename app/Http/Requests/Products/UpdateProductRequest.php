<?php

namespace App\Http\Requests\Products;

class UpdateProductRequest extends AbstractProductRequest
{
    protected function usesSometimes(): bool
    {
        return true;
    }
}
