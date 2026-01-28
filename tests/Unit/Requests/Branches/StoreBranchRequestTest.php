<?php

namespace Tests\Unit\Requests\Branches;

use App\Http\Requests\Branches\StoreBranchRequest;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudStoreRequestTestTrait;

class StoreBranchRequestTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudStoreRequestTestTrait;

    protected function getRequestClass(): string
    {
        return StoreBranchRequest::class;
    }

    protected function getModelClass(): string
    {
        return Branch::class;
    }
}
