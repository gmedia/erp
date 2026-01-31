<?php

namespace Tests\Feature;

use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudTestTrait;

class FiscalYearControllerTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudTestTrait;

    protected $modelClass = FiscalYear::class;
    protected $endpoint = '/api/fiscal-years';
    protected $permissionPrefix = 'fiscal-year';
    protected $structure = ['id', 'name', 'start_date', 'end_date', 'status', 'created_at', 'updated_at'];

    protected function getBasePermissions(): array
    {
        return [
            $this->permissionPrefix,
            "{$this->permissionPrefix}.create",
            "{$this->permissionPrefix}.edit",
            "{$this->permissionPrefix}.delete",
            "{$this->permissionPrefix}.export",
        ];
    }

    public function test_it_returns_paginated_list_with_proper_meta_structure()
    {
        $this->modelClass::query()->delete();
        $user = $this->setUpUserWithPermissions($this->getBasePermissions());
        $this->actingAs($user);

        $baseline = $this->modelClass::count();

        $this->modelClass::factory()->count(25)->create(['status' => 'open']);

        $response = $this->getJson("{$this->endpoint}?per_page=10");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => $this->structure
                ],
                'links',
                'meta'
            ])
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', $baseline + 25);
    }

    public function test_it_creates_resource_successfully()
    {
        $this->modelClass::query()->delete();
        $user = $this->setUpUserWithPermissions($this->getBasePermissions());
        $this->actingAs($user);

        $data = [
            'name' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
        ];
        
        $response = $this->postJson($this->endpoint, $data);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'FY 2026')
            ->assertJsonPath('data.status', 'open');

        $this->assertDatabaseHas('fiscal_years', $data);
    }

    public function test_it_updates_resource_successfully()
    {
        $this->modelClass::query()->delete();
        $user = $this->setUpUserWithPermissions($this->getBasePermissions());
        $this->actingAs($user);

        $resource = $this->modelClass::factory()->create([
            'name' => 'FY 2025',
            'status' => 'open'
        ]);

        $updateData = [
            'name' => 'FY 2025 Updated',
            'start_date' => $resource->start_date->format('Y-m-d'),
            'end_date' => $resource->end_date->format('Y-m-d'),
            'status' => 'closed',
        ];

        $response = $this->putJson("{$this->endpoint}/{$resource->id}", $updateData);

        $response->assertOk()
            ->assertJsonPath('data.name', 'FY 2025 Updated')
            ->assertJsonPath('data.status', 'closed');

        $this->assertDatabaseHas('fiscal_years', array_merge(['id' => $resource->id], $updateData));
    }

    public function test_it_validates_update_request()
    {
        $this->modelClass::query()->delete();
        $user = $this->setUpUserWithPermissions($this->getBasePermissions());
        $this->actingAs($user);

        $resource = $this->modelClass::factory()->create(['name' => 'Original FY', 'status' => 'open']);

        // Test empty name
        $response = $this->putJson("{$this->endpoint}/{$resource->id}", ['name' => '']);
        $response->assertUnprocessable()->assertJsonValidationErrors(['name']);

        // Test invalid status
        $response = $this->putJson("{$this->endpoint}/{$resource->id}", ['status' => 'invalid']);
        $response->assertUnprocessable()->assertJsonValidationErrors(['status']);

        // Test valid partial update (SimpleCrudUpdateRequest usually allows partials, but let's check our implementation)
        // Our UpdateFiscalYearRequest requires status if present, etc.
        $response = $this->putJson("{$this->endpoint}/{$resource->id}", [
            'name' => 'Updated Name',
            'start_date' => $resource->start_date->format('Y-m-d'),
            'end_date' => $resource->end_date->format('Y-m-d'),
            'status' => $resource->status,
        ]);
        $response->assertOk();
    }

    public function test_it_validates_store_request()
    {
        $this->modelClass::query()->delete();
        $user = $this->setUpUserWithPermissions($this->getBasePermissions());
        $this->actingAs($user);

        // Test required fields
        $response = $this->postJson($this->endpoint, []);
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'start_date', 'end_date', 'status']);

        // Test date logical validation
        $response = $this->postJson($this->endpoint, [
            'name' => 'Invalid Dates',
            'start_date' => '2026-12-31',
            'end_date' => '2026-01-01', // Before start_date
            'status' => 'open'
        ]);
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_it_filters_by_status()
    {
        $this->modelClass::query()->delete();
        $user = $this->setUpUserWithPermissions($this->getBasePermissions());
        $this->actingAs($user);

        FiscalYear::factory()->create(['status' => 'open']);
        FiscalYear::factory()->create(['status' => 'closed']);
        FiscalYear::factory()->create(['status' => 'locked']);

        $response = $this->getJson("{$this->endpoint}?status=open");
        $response->assertOk()->assertJsonCount(1, 'data');

        $response = $this->getJson("{$this->endpoint}?status=closed");
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_it_exports_with_status_filter()
    {
        $this->modelClass::query()->delete();
        $user = $this->setUpUserWithPermissions($this->getBasePermissions());
        $this->actingAs($user);

        FiscalYear::factory()->create(['name' => 'FY Open', 'status' => 'open']);
        FiscalYear::factory()->create(['name' => 'FY Closed', 'status' => 'closed']);

        $response = $this->postJson("{$this->endpoint}/export", ['status' => 'open']);

        $response->assertOk()
            ->assertJsonStructure(['url', 'filename']);
        
        $this->assertStringContainsString('fiscal_years_export', $response->json('filename'));
    }
}
