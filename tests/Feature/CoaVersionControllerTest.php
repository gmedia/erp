<?php

namespace Tests\Feature\CoaVersions;

use App\Models\CoaVersion;
use App\Models\Employee;
use App\Models\FiscalYear;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group coa_versions
 */
class CoaVersionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Employee $admin;
    protected FiscalYear $fiscalYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Employee::factory()->create([
            'email' => 'admin@example.com',
        ]);

        $this->fiscalYear = FiscalYear::factory()->create([
            'name' => 'FY 2026',
            'status' => 'open',
        ]);

        // Give admin all coa_version permissions
        $permissions = [
            'coa_version',
            'coa_version.create',
            'coa_version.edit',
            'coa_version.delete',
            'coa_version.export',
        ];

        foreach ($permissions as $p) {
            $permission = Permission::updateOrCreate(['name' => $p], ['display_name' => $p]);
            $this->admin->permissions()->attach($permission);
        }

        $this->actingAs($this->admin->user);
    }

    public function test_index_returns_paginated_coa_versions()
    {
        CoaVersion::factory()->count(15)->create([
            'fiscal_year_id' => $this->fiscalYear->id,
        ]);

        $response = $this->getJson('/api/coa-versions');

        $response->assertStatus(200)
            ->assertJsonCount(15, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'fiscal_year_id',
                        'fiscal_year' => ['id', 'name'],
                        'status',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'links',
                'meta',
            ]);
    }

    public function test_index_filters_by_search_name()
    {
        CoaVersion::factory()->create(['name' => 'Version Alpha', 'fiscal_year_id' => $this->fiscalYear->id]);
        CoaVersion::factory()->create(['name' => 'Version Beta', 'fiscal_year_id' => $this->fiscalYear->id]);

        $response = $this->getJson('/api/coa-versions?search=Alpha');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Version Alpha');
    }

    public function test_index_filters_by_fiscal_year()
    {
        $otherFY = FiscalYear::factory()->create(['name' => 'FY 2027']);
        
        CoaVersion::factory()->create(['name' => 'V1 2026', 'fiscal_year_id' => $this->fiscalYear->id]);
        CoaVersion::factory()->create(['name' => 'V1 2027', 'fiscal_year_id' => $otherFY->id]);

        $response = $this->getJson("/api/coa-versions?fiscal_year_id={$this->fiscalYear->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'V1 2026');
    }

    public function test_store_creates_new_coa_version()
    {
        $payload = [
            'name' => 'New Version',
            'fiscal_year_id' => $this->fiscalYear->id,
            'status' => 'draft',
        ];

        $response = $this->postJson('/api/coa-versions', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('coa_versions', [
            'name' => 'New Version',
            'fiscal_year_id' => $this->fiscalYear->id,
            'status' => 'draft',
        ]);
    }

    public function test_show_returns_coa_version_details()
    {
        $version = CoaVersion::factory()->create([
            'fiscal_year_id' => $this->fiscalYear->id,
        ]);

        $response = $this->getJson("/api/coa-versions/{$version->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $version->id)
            ->assertJsonPath('data.name', $version->name);
    }

    public function test_update_modifies_existing_coa_version()
    {
        $version = CoaVersion::factory()->create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'status' => 'draft',
        ]);

        $payload = [
            'name' => 'Updated Version',
            'fiscal_year_id' => $this->fiscalYear->id,
            'status' => 'active',
        ];

        $response = $this->putJson("/api/coa-versions/{$version->id}", $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('coa_versions', [
            'id' => $version->id,
            'name' => 'Updated Version',
            'status' => 'active',
        ]);
    }

    public function test_destroy_deletes_coa_version()
    {
        $version = CoaVersion::factory()->create([
            'fiscal_year_id' => $this->fiscalYear->id,
        ]);

        $response = $this->deleteJson("/api/coa-versions/{$version->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('coa_versions', ['id' => $version->id]);
    }

    public function test_export_returns_xlsx_file()
    {
        CoaVersion::factory()->count(5)->create([
            'fiscal_year_id' => $this->fiscalYear->id,
        ]);

        $response = $this->postJson('/api/coa-versions/export');

        $response->assertStatus(200)
            ->assertJsonStructure(['url', 'filename']);

        $this->assertStringContainsString('coa_versions_export', $response->json('filename'));
    }

    public function test_unauthorized_access_is_forbidden()
    {
        $user = Employee::factory()->create()->user;
        $this->actingAs($user);

        $response = $this->getJson('/api/coa-versions');
        $response->assertStatus(403);
    }
}
