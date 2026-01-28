<?php

namespace Tests\Unit\Resources\Branches;

use App\Http\Resources\Branches\BranchResource;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudResourceTestTrait;

class BranchResourceTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudResourceTestTrait;

    protected function getResourceClass(): string
    {
        return BranchResource::class;
    }

    protected function getModelClass(): string
    {
        return Branch::class;
    }
}
