<?php

namespace Tests\Unit\Requests\Units;

use App\Http\Requests\Units\IndexUnitRequest;
use Tests\TestCase;
use Tests\Traits\SimpleCrudIndexRequestTestTrait;

class IndexUnitRequestTest extends TestCase
{
    use SimpleCrudIndexRequestTestTrait;

    protected function getRequestClass(): string
    {
        return IndexUnitRequest::class;
    }
}
