<?php

namespace Tests\Unit\Domain\CustomerCategories;

use App\Domain\CustomerCategories\CustomerCategoryFilterService;
use App\Models\CustomerCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCategoryFilterServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_filter_by_search(): void
    {
        CustomerCategory::factory()->create(['name' => 'Alpha']);
        CustomerCategory::factory()->create(['name' => 'Beta']);

        $service = new CustomerCategoryFilterService();
        $query = CustomerCategory::query();

        $service->applySearch($query, 'Alph', ['name']);

        $this->assertEquals(1, $query->count());
        $this->assertEquals('Alpha', $query->first()->name);
    }
}
