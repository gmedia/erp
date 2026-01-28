<?php

namespace Tests\Unit\Resources\Branches;

use App\Http\Resources\Branches\BranchCollection;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudCollectionTestTrait;

class BranchCollectionTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudCollectionTestTrait;

    protected function getCollectionClass(): string
    {
        return BranchCollection::class;
    }

    protected function getModelClass(): string
    {
        return Branch::class;
    }
}
