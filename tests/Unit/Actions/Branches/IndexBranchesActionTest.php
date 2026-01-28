<?php

namespace Tests\Unit\Actions\Branches;

use App\Actions\Branches\IndexBranchesAction;
use App\Http\Requests\Branches\IndexBranchRequest;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudIndexActionTestTrait;

class IndexBranchesActionTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudIndexActionTestTrait;

    protected function getActionClass(): string
    {
        return IndexBranchesAction::class;
    }

    protected function getModelClass(): string
    {
        return Branch::class;
    }

    protected function getRequestClass(): string
    {
        return IndexBranchRequest::class;
    }
}
