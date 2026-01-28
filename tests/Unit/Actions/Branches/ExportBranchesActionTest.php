<?php

namespace Tests\Unit\Actions\Branches;

use App\Actions\Branches\ExportBranchesAction;
use App\Http\Requests\Branches\ExportBranchRequest;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudExportActionTestTrait;

class ExportBranchesActionTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudExportActionTestTrait;

    protected function getActionClass(): string
    {
        return ExportBranchesAction::class;
    }

    protected function getModelClass(): string
    {
        return Branch::class;
    }

    protected function getRequestClass(): string
    {
        return ExportBranchRequest::class;
    }

    protected function getExpectedFilenamePrefix(): string
    {
        return 'branches';
    }
}
