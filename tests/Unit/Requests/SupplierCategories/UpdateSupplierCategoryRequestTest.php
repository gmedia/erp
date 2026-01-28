<?php

namespace Tests\Unit\Requests\SupplierCategories;

use App\Http\Requests\SupplierCategories\UpdateSupplierCategoryRequest;
use App\Models\SupplierCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudUpdateRequestTestTrait;

class UpdateSupplierCategoryRequestTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudUpdateRequestTestTrait;

    protected function getRequestClass(): string
    {
        return UpdateSupplierCategoryRequest::class;
    }

    protected function getModelClass(): string
    {
        return SupplierCategory::class;
    }

    protected function getRouteParameterName(): string
    {
        return 'supplier_category';
    }
}
