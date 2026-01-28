<?php

namespace Tests\Unit\Actions\CustomerCategories;

use App\Actions\CustomerCategories\ExportCustomerCategoriesAction;
use App\Http\Requests\CustomerCategories\ExportCustomerCategoryRequest;
use App\Models\CustomerCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudExportActionTestTrait;

class ExportCustomerCategoriesActionTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudExportActionTestTrait;

    protected function getActionClass(): string
    {
        return ExportCustomerCategoriesAction::class;
    }

    protected function getModelClass(): string
    {
        return CustomerCategory::class;
    }

    protected function getRequestClass(): string
    {
        return ExportCustomerCategoryRequest::class;
    }

    protected function getExpectedFilenamePrefix(): string
    {
        return 'customer_categories';
    }
}
