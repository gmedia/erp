<?php

namespace Tests\Unit\Actions\CustomerCategories;

use App\Actions\CustomerCategories\IndexCustomerCategoriesAction;
use App\Http\Requests\CustomerCategories\IndexCustomerCategoryRequest;
use App\Models\CustomerCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudIndexActionTestTrait;

class IndexCustomerCategoriesActionTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudIndexActionTestTrait;

    protected function getActionClass(): string
    {
        return IndexCustomerCategoriesAction::class;
    }

    protected function getModelClass(): string
    {
        return CustomerCategory::class;
    }

    protected function getRequestClass(): string
    {
        return IndexCustomerCategoryRequest::class;
    }
}
