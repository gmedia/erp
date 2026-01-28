<?php

namespace Tests\Unit\Requests\Branches;

use App\Http\Requests\Branches\UpdateBranchRequest;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudUpdateRequestTestTrait;

class UpdateBranchRequestTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudUpdateRequestTestTrait;

    protected function getRequestClass(): string
    {
        return UpdateBranchRequest::class;
    }

    protected function getModelClass(): string
    {
        return Branch::class;
    }

    protected function getRouteParameterName(): string
    {
        return 'branch';
    }
}
