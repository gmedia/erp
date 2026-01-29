<?php

namespace Tests\Unit\Actions\ProductCategories;

use App\Actions\ProductCategories\ExportProductCategoriesAction;
use App\Http\Requests\ProductCategories\ExportProductCategoryRequest;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudExportActionTestTrait;

class ExportProductCategoriesActionTest extends TestCase
{
    use RefreshDatabase, SimpleCrudExportActionTestTrait;

    protected function getActionClass(): string
    {
        return ExportProductCategoriesAction::class;
    }

    protected function getModelClass(): string
    {
        return ProductCategory::class;
    }

    protected function getRequestClass(): string
    {
        return ExportProductCategoryRequest::class;
    }

    protected function getExpectedFilenamePrefix(): string
    {
        return 'product_categories';
    }
}
