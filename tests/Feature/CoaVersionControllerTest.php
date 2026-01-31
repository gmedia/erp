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
