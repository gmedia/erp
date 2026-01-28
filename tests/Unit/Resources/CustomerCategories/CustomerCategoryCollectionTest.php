<?php

namespace Tests\Unit\Resources\CustomerCategories;

use App\Http\Resources\CustomerCategories\CustomerCategoryCollection;
use App\Models\CustomerCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudCollectionTestTrait;

class CustomerCategoryCollectionTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudCollectionTestTrait;

    protected function getCollectionClass(): string
    {
        return CustomerCategoryCollection::class;
    }

    protected function getModelClass(): string
    {
        return CustomerCategory::class;
    }
}
