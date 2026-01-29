<?php

namespace Tests\Unit\Domain\ProductCategories;

use App\Domain\ProductCategories\ProductCategoryFilterService;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudFilterServiceTestTrait;

class ProductCategoryFilterServiceTest extends TestCase
{
    use RefreshDatabase, SimpleCrudFilterServiceTestTrait;

    protected function getFilterServiceClass(): string
    {
        return ProductCategoryFilterService::class;
    }

    protected function getModelClass(): string
    {
        return ProductCategory::class;
    }
}
