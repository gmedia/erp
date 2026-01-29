<?php

namespace Tests\Unit\Resources\ProductCategories;

use App\Http\Resources\ProductCategories\ProductCategoryCollection;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudCollectionTestTrait;

class ProductCategoryCollectionTest extends TestCase
{
    use RefreshDatabase, SimpleCrudCollectionTestTrait;

    protected function getCollectionClass(): string
    {
        return ProductCategoryCollection::class;
    }

    protected function getModelClass(): string
    {
        return ProductCategory::class;
    }
}
