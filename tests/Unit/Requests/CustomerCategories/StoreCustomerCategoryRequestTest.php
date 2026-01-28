<?php

namespace Tests\Unit\Requests\CustomerCategories;

use App\Http\Requests\CustomerCategories\StoreCustomerCategoryRequest;
use App\Models\CustomerCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudStoreRequestTestTrait;

class StoreCustomerCategoryRequestTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudStoreRequestTestTrait;

    protected function getRequestClass(): string
    {
        return StoreCustomerCategoryRequest::class;
    }

    protected function getModelClass(): string
    {
        return CustomerCategory::class;
    }
}
