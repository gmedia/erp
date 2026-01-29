<?php

namespace Tests\Feature;

use App\Exports\ProductCategoryExport;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudExportTestTrait;

class ProductCategoryExportTest extends TestCase
{
    use RefreshDatabase, SimpleCrudExportTestTrait;

    protected function getExportClass(): string
    {
        return ProductCategoryExport::class;
    }

    protected function getModelClass(): string
    {
        return ProductCategory::class;
    }

    protected function getSampleData(): array
    {
        return [
            'match' => 'Finished Goods',
            'others' => ['Raw Materials', 'SaaS'],
        ];
    }
}
