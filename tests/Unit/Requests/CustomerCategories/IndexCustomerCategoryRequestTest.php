<?php

namespace Tests\Unit\Requests\CustomerCategories;

use App\Http\Requests\CustomerCategories\IndexCustomerCategoryRequest;
use Tests\TestCase;
use Tests\Traits\SimpleCrudIndexRequestTestTrait;

class IndexCustomerCategoryRequestTest extends TestCase
{
    use SimpleCrudIndexRequestTestTrait;

    protected function getRequestClass(): string
    {
        return IndexCustomerCategoryRequest::class;
    }
}
