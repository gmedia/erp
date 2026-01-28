<?php

namespace Tests\Unit\Actions\SupplierCategories;

use App\Actions\SupplierCategories\IndexSupplierCategoriesAction;
use App\Http\Requests\SupplierCategories\IndexSupplierCategoryRequest;
use App\Models\SupplierCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudIndexActionTestTrait;

class IndexSupplierCategoriesActionTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudIndexActionTestTrait;

    protected function getActionClass(): string
    {
        return IndexSupplierCategoriesAction::class;
    }

    protected function getModelClass(): string
    {
        return SupplierCategory::class;
    }

    protected function getRequestClass(): string
    {
        return IndexSupplierCategoryRequest::class;
    }
}
