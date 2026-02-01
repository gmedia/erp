<?php

namespace Tests\Unit\Domain\CoaVersions;

use App\Domain\CoaVersions\CoaVersionFilterService;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoaVersionFilterServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CoaVersionFilterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CoaVersionFilterService();
    }

    public function test_apply_search_filters_by_name()
    {
        CoaVersion::factory()->create(['name' => 'Alpha']);
        CoaVersion::factory()->create(['name' => 'Beta']);

        $query = CoaVersion::query();
        $this->service->applySearch($query, 'Alpha', ['name']);

        $this->assertEquals(1, $query->count());
        $this->assertEquals('Alpha', $query->first()->name);
    }

    public function test_apply_advanced_filters()
    {
        $fy1 = FiscalYear::factory()->create();
        $fy2 = FiscalYear::factory()->create();

        CoaVersion::factory()->create(['status' => 'draft', 'fiscal_year_id' => $fy1->id]);
        CoaVersion::factory()->create(['status' => 'active', 'fiscal_year_id' => $fy2->id]);

        // Test status filter
        $query = CoaVersion::query();
        $this->service->applyAdvancedFilters($query, ['status' => 'draft']);
        $this->assertEquals(1, $query->count());
        $this->assertEquals('draft', $query->first()->status);

        // Test fiscal year filter
        $query = CoaVersion::query();
        $this->service->applyAdvancedFilters($query, ['fiscal_year_id' => $fy1->id]);
        $this->assertEquals(1, $query->count());
        $this->assertEquals($fy1->id, $query->first()->fiscal_year_id);
        
        // Test combined filters
        $query = CoaVersion::query();
        $this->service->applyAdvancedFilters($query, ['status' => 'active', 'fiscal_year_id' => $fy2->id]);
        $this->assertEquals(1, $query->count());
        $this->assertEquals('active', $query->first()->status);
        $this->assertEquals($fy2->id, $query->first()->fiscal_year_id);
    }

    public function test_apply_sorting()
    {
        CoaVersion::factory()->create(['name' => 'B']);
        CoaVersion::factory()->create(['name' => 'A']);

        $query = CoaVersion::query();
        $this->service->applySorting($query, 'name', 'asc', ['name']);

        $this->assertEquals('A', $query->first()->name);
    }
}
