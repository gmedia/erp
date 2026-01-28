<?php

namespace Tests\Unit\Resources\CustomerCategories;

use App\Http\Resources\CustomerCategories\CustomerCategoryResource;
use App\Models\CustomerCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudResourceTestTrait;

class CustomerCategoryResourceTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudResourceTestTrait;

    protected function getResourceClass(): string
    {
        return CustomerCategoryResource::class;
    }

    protected function getModelClass(): string
    {
        return CustomerCategory::class;
    }
}
