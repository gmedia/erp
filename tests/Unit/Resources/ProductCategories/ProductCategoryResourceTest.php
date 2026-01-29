<?php

namespace Tests\Unit\Resources\ProductCategories;

use App\Http\Resources\ProductCategories\ProductCategoryResource;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudResourceTestTrait;

class ProductCategoryResourceTest extends TestCase
{
    use RefreshDatabase, SimpleCrudResourceTestTrait;

    protected function getResourceClass(): string
    {
        return ProductCategoryResource::class;
    }

    protected function getModelClass(): string
    {
        return ProductCategory::class;
    }
}
