<?php

namespace Tests\Feature;

use App\Exports\DepartmentExport;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudExportTestTrait;

class DepartmentExportTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudExportTestTrait;

    protected function getExportClass(): string
    {
        return DepartmentExport::class;
    }

    protected function getModelClass(): string
    {
        return Department::class;
    }

    protected function getSampleData(): array
    {
        return [
            'match' => 'Engineering Department',
            'others' => ['Marketing Department', 'Sales Department'],
        ];
    }
}
