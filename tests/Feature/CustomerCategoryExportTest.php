<?php

namespace Tests\Feature;

use App\Exports\CustomerCategoryExport;
use App\Models\CustomerCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudExportTestTrait;

class CustomerCategoryExportTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudExportTestTrait;

    protected function getExportClass(): string
    {
        return CustomerCategoryExport::class;
    }

    protected function getModelClass(): string
    {
        return CustomerCategory::class;
    }

    protected function getSampleData(): array
    {
        return [
            'match' => 'Retail Customer',
            'others' => ['Wholesale Customer', 'Corporate Customer'],
        ];
    }
}
