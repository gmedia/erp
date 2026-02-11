<?php

use App\Models\CoaVersion;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('coa-versions');

describe('COA Version API Endpoints', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions([
            'coa_version',
            'coa_version.create',
            'coa_version.edit',
            'coa_version.delete',
            'coa_version.export',
        ]);
        actingAs($user);
        
        $this->fiscalYear = FiscalYear::factory()->create([
            'name' => 'FY 2026',
            'status' => 'open',
        ]);
    });

    test('index returns paginated coa versions', function () {
        CoaVersion::factory()->count(15)->create([
            'fiscal_year_id' => $this->fiscalYear->id,
        ]);

        $response = getJson('/api/coa-versions');

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
    });

    test('index filters by search name', function () {
        CoaVersion::factory()->create(['name' => 'Version Alpha', 'fiscal_year_id' => $this->fiscalYear->id]);
        CoaVersion::factory()->create(['name' => 'Version Beta', 'fiscal_year_id' => $this->fiscalYear->id]);

        $response = getJson('/api/coa-versions?search=Alpha');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Version Alpha');
    });

    test('index filters by status', function () {
        CoaVersion::factory()->create(['status' => 'active', 'fiscal_year_id' => $this->fiscalYear->id]);
        CoaVersion::factory()->create(['status' => 'draft', 'fiscal_year_id' => $this->fiscalYear->id]);

        $response = getJson('/api/coa-versions?status=active');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'active');
    });

    test('index sorts by name', function () {
        CoaVersion::factory()->create(['name' => 'B Version', 'fiscal_year_id' => $this->fiscalYear->id]);
        CoaVersion::factory()->create(['name' => 'A Version', 'fiscal_year_id' => $this->fiscalYear->id]);

        $response = getJson('/api/coa-versions?sort_by=name&sort_direction=asc');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.name', 'A Version')
            ->assertJsonPath('data.1.name', 'B Version');
    });

    test('index sorts by status', function () {
        CoaVersion::factory()->create(['status' => 'draft', 'fiscal_year_id' => $this->fiscalYear->id]);
        CoaVersion::factory()->create(['status' => 'active', 'fiscal_year_id' => $this->fiscalYear->id]);
        CoaVersion::factory()->create(['status' => 'archived', 'fiscal_year_id' => $this->fiscalYear->id]);

        $response = getJson('/api/coa-versions?sort_by=status&sort_direction=asc');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.status', 'draft')
            ->assertJsonPath('data.1.status', 'active')
            ->assertJsonPath('data.2.status', 'archived');
    });

    test('index sorts by fiscal year name', function () {
        $fyA = FiscalYear::factory()->create(['name' => 'FY 2024', 'status' => 'open']);
        $fyB = FiscalYear::factory()->create(['name' => 'FY 2025', 'status' => 'open']);

        CoaVersion::factory()->create(['name' => 'V FY2025', 'fiscal_year_id' => $fyB->id]);
        CoaVersion::factory()->create(['name' => 'V FY2024', 'fiscal_year_id' => $fyA->id]);

        $response = getJson('/api/coa-versions?sort_by=fiscal_year.name&sort_direction=asc&per_page=10');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.fiscal_year.name', 'FY 2024')
            ->assertJsonPath('data.1.fiscal_year.name', 'FY 2025');
    });

    test('index filters by fiscal year', function () {
        $otherFY = FiscalYear::factory()->create(['name' => 'FY 2027']);
        
        CoaVersion::factory()->create(['name' => 'V1 2026', 'fiscal_year_id' => $this->fiscalYear->id]);
        CoaVersion::factory()->create(['name' => 'V1 2027', 'fiscal_year_id' => $otherFY->id]);

        $response = getJson("/api/coa-versions?fiscal_year_id={$this->fiscalYear->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'V1 2026');
    });

    test('store creates new coa version', function () {
        $payload = [
            'name' => 'New Version',
            'fiscal_year_id' => $this->fiscalYear->id,
            'status' => 'draft',
        ];

        $response = postJson('/api/coa-versions', $payload);

        $response->assertStatus(201);
        assertDatabaseHas('coa_versions', [
            'name' => 'New Version',
            'fiscal_year_id' => $this->fiscalYear->id,
            'status' => 'draft',
        ]);
    });

    test('store fails with invalid status', function () {
        $payload = [
            'name' => 'New Version',
            'fiscal_year_id' => $this->fiscalYear->id,
            'status' => 'invalid-status',
        ];

        $response = postJson('/api/coa-versions', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    });

    test('show returns coa version details', function () {
        $version = CoaVersion::factory()->create([
            'fiscal_year_id' => $this->fiscalYear->id,
        ]);

        $response = getJson("/api/coa-versions/{$version->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $version->id)
            ->assertJsonPath('data.name', $version->name);
    });

    test('update modifies existing coa version', function () {
        $version = CoaVersion::factory()->create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'status' => 'draft',
        ]);

        $payload = [
            'name' => 'Updated Version',
            'fiscal_year_id' => $this->fiscalYear->id,
            'status' => 'active',
        ];

        $response = putJson("/api/coa-versions/{$version->id}", $payload);

        $response->assertStatus(200);
        assertDatabaseHas('coa_versions', [
            'id' => $version->id,
            'name' => 'Updated Version',
            'status' => 'active',
        ]);
    });

    test('update fails with duplicate name in same fiscal year', function () {
        CoaVersion::factory()->create(['name' => 'Existing Version', 'fiscal_year_id' => $this->fiscalYear->id]);
        $version = CoaVersion::factory()->create(['name' => 'My Version', 'fiscal_year_id' => $this->fiscalYear->id]);

        $payload = [
            'name' => 'Existing Version',
            'fiscal_year_id' => $this->fiscalYear->id,
            'status' => 'active',
        ];

        $response = putJson("/api/coa-versions/{$version->id}", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    test('destroy deletes coa version', function () {
        $version = CoaVersion::factory()->create([
            'fiscal_year_id' => $this->fiscalYear->id,
        ]);

        $response = deleteJson("/api/coa-versions/{$version->id}");

        $response->assertStatus(204);
        assertDatabaseMissing('coa_versions', ['id' => $version->id]);
    });

    test('export returns download url', function () {
        CoaVersion::factory()->count(5)->create([
            'fiscal_year_id' => $this->fiscalYear->id,
        ]);

        $response = postJson('/api/coa-versions/export');

        $response->assertStatus(200)
            ->assertJsonStructure(['url', 'filename']);

        expect($response->json('filename'))->toContain('coa_versions_export');
    });

    test('unauthorized access is forbidden', function () {
        $user = createTestUserWithPermissions([]); // No permissions
        actingAs($user);

        getJson('/api/coa-versions')->assertForbidden();
    });
});
