<?php

namespace Tests\Unit\Requests\ProductCategories;

use App\Http\Requests\ProductCategories\IndexProductCategoryRequest;
use Tests\TestCase;
use Tests\Traits\SimpleCrudIndexRequestTestTrait;

class IndexProductCategoryRequestTest extends TestCase
{
    use SimpleCrudIndexRequestTestTrait;

    protected function getRequestClass(): string
    {
        return IndexProductCategoryRequest::class;
    }
}
