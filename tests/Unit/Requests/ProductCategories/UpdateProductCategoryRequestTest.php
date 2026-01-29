<?php

namespace Tests\Unit\Requests\ProductCategories;

use App\Http\Requests\ProductCategories\UpdateProductCategoryRequest;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudUpdateRequestTestTrait;

class UpdateProductCategoryRequestTest extends TestCase
{
    use RefreshDatabase, SimpleCrudUpdateRequestTestTrait;

    protected function getRequestClass(): string
    {
        return UpdateProductCategoryRequest::class;
    }

    protected function getModelClass(): string
    {
        return ProductCategory::class;
    }

    protected function getRouteParameterName(): string
    {
        return 'productCategory';
    }
}
