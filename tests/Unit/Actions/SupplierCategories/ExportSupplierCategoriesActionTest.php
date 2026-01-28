<?php

namespace Tests\Unit\Actions\SupplierCategories;

use App\Actions\SupplierCategories\ExportSupplierCategoriesAction;
use App\Http\Requests\SupplierCategories\ExportSupplierCategoryRequest;
use App\Models\SupplierCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudExportActionTestTrait;

class ExportSupplierCategoriesActionTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudExportActionTestTrait;

    protected function getActionClass(): string
    {
        return ExportSupplierCategoriesAction::class;
    }

    protected function getModelClass(): string
    {
        return SupplierCategory::class;
    }

    protected function getRequestClass(): string
    {
        return ExportSupplierCategoryRequest::class;
    }

    protected function getExpectedFilenamePrefix(): string
    {
        return 'supplier_categories';
    }
}
