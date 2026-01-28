<?php

namespace Tests\Unit\Requests\Branches;

use App\Http\Requests\Branches\IndexBranchRequest;
use Tests\TestCase;
use Tests\Traits\SimpleCrudIndexRequestTestTrait;

class IndexBranchRequestTest extends TestCase
{
    use SimpleCrudIndexRequestTestTrait;

    protected function getRequestClass(): string
    {
        return IndexBranchRequest::class;
    }
}
