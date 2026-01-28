<?php

namespace Tests\Unit\Actions\Departments;

use App\Actions\Departments\ExportDepartmentsAction;
use App\Http\Requests\Departments\ExportDepartmentRequest;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudExportActionTestTrait;

class ExportDepartmentsActionTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudExportActionTestTrait;

    protected function getActionClass(): string
    {
        return ExportDepartmentsAction::class;
    }

    protected function getModelClass(): string
    {
        return Department::class;
    }

    protected function getRequestClass(): string
    {
        return ExportDepartmentRequest::class;
    }

    protected function getExpectedFilenamePrefix(): string
    {
        return 'departments';
    }
}
