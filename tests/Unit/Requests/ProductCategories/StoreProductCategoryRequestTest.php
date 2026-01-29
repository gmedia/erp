<?php

namespace Tests\Unit\Requests\ProductCategories;

use App\Http\Requests\ProductCategories\StoreProductCategoryRequest;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudStoreRequestTestTrait;

class StoreProductCategoryRequestTest extends TestCase
{
    use RefreshDatabase, SimpleCrudStoreRequestTestTrait;

    protected function getRequestClass(): string
    {
        return StoreProductCategoryRequest::class;
    }

    protected function getModelClass(): string
    {
        return ProductCategory::class;
    }
}
