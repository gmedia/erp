<?php

namespace Tests\Unit\Resources\SupplierCategories;

use App\Http\Resources\SupplierCategories\SupplierCategoryResource;
use App\Models\SupplierCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudResourceTestTrait;

class SupplierCategoryResourceTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudResourceTestTrait;

    protected function getResourceClass(): string
    {
        return SupplierCategoryResource::class;
    }

    protected function getModelClass(): string
    {
        return SupplierCategory::class;
    }
}
