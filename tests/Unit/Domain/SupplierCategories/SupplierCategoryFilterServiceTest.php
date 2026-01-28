<?php

namespace Tests\Unit\Domain\SupplierCategories;

use App\Domain\SupplierCategories\SupplierCategoryFilterService;
use App\Models\SupplierCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudFilterServiceTestTrait;

class SupplierCategoryFilterServiceTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudFilterServiceTestTrait;

    protected function getFilterServiceClass(): string
    {
        return SupplierCategoryFilterService::class;
    }

    protected function getModelClass(): string
    {
        return SupplierCategory::class;
    }
}
