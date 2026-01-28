<?php

namespace Tests\Unit\Domain\CustomerCategories;

use App\Domain\CustomerCategories\CustomerCategoryFilterService;
use App\Models\CustomerCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudFilterServiceTestTrait;

class CustomerCategoryFilterServiceTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudFilterServiceTestTrait;

    protected function getFilterServiceClass(): string
    {
        return CustomerCategoryFilterService::class;
    }

    protected function getModelClass(): string
    {
        return CustomerCategory::class;
    }
}
