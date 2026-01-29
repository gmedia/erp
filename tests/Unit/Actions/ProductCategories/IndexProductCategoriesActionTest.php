<?php

namespace Tests\Unit\Actions\ProductCategories;

use App\Actions\ProductCategories\IndexProductCategoriesAction;
use App\Http\Requests\ProductCategories\IndexProductCategoryRequest;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudIndexActionTestTrait;

class IndexProductCategoriesActionTest extends TestCase
{
    use RefreshDatabase, SimpleCrudIndexActionTestTrait;

    protected function getActionClass(): string
    {
        return IndexProductCategoriesAction::class;
    }

    protected function getModelClass(): string
    {
        return ProductCategory::class;
    }

    protected function getRequestClass(): string
    {
        return IndexProductCategoryRequest::class;
    }
}
