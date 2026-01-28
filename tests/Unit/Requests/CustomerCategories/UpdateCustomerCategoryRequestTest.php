<?php

namespace Tests\Unit\Requests\CustomerCategories;

use App\Http\Requests\CustomerCategories\UpdateCustomerCategoryRequest;
use App\Models\CustomerCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudUpdateRequestTestTrait;

class UpdateCustomerCategoryRequestTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudUpdateRequestTestTrait;

    protected function getRequestClass(): string
    {
        return UpdateCustomerCategoryRequest::class;
    }

    protected function getModelClass(): string
    {
        return CustomerCategory::class;
    }

    protected function getRouteParameterName(): string
    {
        return 'customer_category';
    }
}
