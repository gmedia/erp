<?php

namespace Tests\Unit\Requests\SupplierCategories;

use App\Http\Requests\SupplierCategories\StoreSupplierCategoryRequest;
use App\Models\SupplierCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudStoreRequestTestTrait;

class StoreSupplierCategoryRequestTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudStoreRequestTestTrait;

    protected function getRequestClass(): string
    {
        return StoreSupplierCategoryRequest::class;
    }

    protected function getModelClass(): string
    {
        return SupplierCategory::class;
    }
}
