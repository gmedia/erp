<?php

use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('fiscal-years');

describe('Fiscal Year API Endpoints', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions([
            'fiscal-year',
            'fiscal-year.create',
            'fiscal-year.edit',
            'fiscal-year.delete',
            'fiscal-year.export',
        ]);
        actingAs($user);
    });

    test('index returns paginated fiscal years with proper meta structure', function () {
        FiscalYear::query()->delete();
        FiscalYear::factory()->count(25)->create(['status' => 'open']);

        $response = getJson('/api/fiscal-years?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'start_date', 'end_date', 'status', 'created_at', 'updated_at']
                ],
                'links',
                'meta'
            ])
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 25);
    });

    test('store creates fiscal year successfully', function () {
        FiscalYear::query()->delete();
        $data = [
            'name' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
        ];
        
        $response = postJson('/api/fiscal-years', $data);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'FY 2026')
            ->assertJsonPath('data.status', 'open');

        assertDatabaseHas('fiscal_years', $data);
    });

    test('store validates required fields', function () {
        $response = postJson('/api/fiscal-years', []);
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'start_date', 'end_date', 'status']);
    });

    test('store validates date logic', function () {
        $response = postJson('/api/fiscal-years', [
            'name' => 'Invalid Dates',
            'start_date' => '2026-12-31',
            'end_date' => '2026-01-01',
            'status' => 'open'
        ]);
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);
    });

    test('show returns fiscal year successfully', function () {
        $resource = FiscalYear::factory()->create();

        $response = getJson("/api/fiscal-years/{$resource->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $resource->id)
            ->assertJsonPath('data.name', $resource->name);
    });

    test('update modifies fiscal year successfully', function () {
        $resource = FiscalYear::factory()->create(['name' => 'Old Name', 'status' => 'open']);

        $updateData = [
            'name' => 'Updated Name',
            'start_date' => $resource->start_date->format('Y-m-d'),
            'end_date' => $resource->end_date->format('Y-m-d'),
            'status' => 'closed',
        ];

        $response = putJson("/api/fiscal-years/{$resource->id}", $updateData);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.status', 'closed');

        assertDatabaseHas('fiscal_years', array_merge(['id' => $resource->id], $updateData));
    });

    test('update validates input', function () {
        $resource = FiscalYear::factory()->create();

        $response = putJson("/api/fiscal-years/{$resource->id}", ['name' => '']);
        $response->assertUnprocessable()->assertJsonValidationErrors(['name']);

        $response = putJson("/api/fiscal-years/{$resource->id}", ['status' => 'invalid']);
        $response->assertUnprocessable()->assertJsonValidationErrors(['status']);
    });

    test('destroy removes fiscal year successfully', function () {
        $resource = FiscalYear::factory()->create();

        $response = deleteJson("/api/fiscal-years/{$resource->id}");

        $response->assertNoContent();
        assertDatabaseMissing('fiscal_years', ['id' => $resource->id]);
    });

    test('index filters by status', function () {
        FiscalYear::query()->delete();
        FiscalYear::factory()->create(['status' => 'open']);
        FiscalYear::factory()->create(['status' => 'closed']);

        $response = getJson('/api/fiscal-years?status=open');
        $response->assertOk()->assertJsonCount(1, 'data');
    });

    test('export returns download url', function () {
        FiscalYear::factory()->count(5)->create();

        $response = postJson('/api/fiscal-years/export');

        $response->assertOk()
            ->assertJsonStructure(['url', 'filename']);
        
        expect($response->json('filename'))->toContain('fiscal_years_export');
    });

    test('enforces permissions', function () {
        $user = createTestUserWithPermissions(['fiscal-year']); // View only
        actingAs($user);

        postJson('/api/fiscal-years', ['name' => 'Fail'])->assertForbidden();

        $resource = FiscalYear::factory()->create();
        putJson("/api/fiscal-years/{$resource->id}", ['name' => 'Fail'])->assertForbidden();
        deleteJson("/api/fiscal-years/{$resource->id}")->assertForbidden();
    });
});
