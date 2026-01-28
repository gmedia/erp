<?php

namespace Tests\Unit\Domain\Branches;

use App\Domain\Branches\BranchFilterService;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudFilterServiceTestTrait;

class BranchFilterServiceTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudFilterServiceTestTrait;

    protected function getFilterServiceClass(): string
    {
        return BranchFilterService::class;
    }

    protected function getModelClass(): string
    {
        return Branch::class;
    }
}
