<?php

namespace Tests\Unit\Resources\SupplierCategories;

use App\Http\Resources\SupplierCategories\SupplierCategoryCollection;
use App\Models\SupplierCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudCollectionTestTrait;

class SupplierCategoryCollectionTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudCollectionTestTrait;

    protected function getCollectionClass(): string
    {
        return SupplierCategoryCollection::class;
    }

    protected function getModelClass(): string
    {
        return SupplierCategory::class;
    }
}
