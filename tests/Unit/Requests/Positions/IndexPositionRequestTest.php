<?php

namespace Tests\Unit\Requests\Positions;

use App\Http\Requests\Positions\IndexPositionRequest;
use Tests\TestCase;
use Tests\Traits\SimpleCrudIndexRequestTestTrait;

class IndexPositionRequestTest extends TestCase
{
    use SimpleCrudIndexRequestTestTrait;

    protected function getRequestClass(): string
    {
        return IndexPositionRequest::class;
    }
}
