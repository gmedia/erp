<?php

namespace Tests\Unit\Requests\SupplierCategories;

use App\Http\Requests\SupplierCategories\IndexSupplierCategoryRequest;
use Tests\TestCase;
use Tests\Traits\SimpleCrudIndexRequestTestTrait;

class IndexSupplierCategoryRequestTest extends TestCase
{
    use SimpleCrudIndexRequestTestTrait;

    protected function getRequestClass(): string
    {
        return IndexSupplierCategoryRequest::class;
    }
}
