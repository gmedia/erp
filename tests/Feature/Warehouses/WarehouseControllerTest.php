<?php

use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('warehouses');

describe('Warehouse API Endpoints', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions([
            'warehouse',
            'warehouse.create',
            'warehouse.edit',
            'warehouse.delete',
        ]);

        actingAs($user);
    });

    test('index returns paginated warehouses', function () {
        $existingCount = Warehouse::count();
        $needed = 15 - $existingCount;

        if ($needed > 0) {
            Warehouse::factory()->count($needed)->create();
        }

        $response = getJson('/api/warehouses?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'branch_id', 'branch', 'code', 'name', 'created_at', 'updated_at'],
                ],
                'meta' => ['total', 'per_page', 'current_page'],
            ]);

        expect($response->json('meta.total'))->toBe(15)
            ->and($response->json('data'))->toHaveCount(10);
    });

    test('index supports search filtering', function () {
        Warehouse::query()->delete();

        Warehouse::factory()->create(['name' => 'XQZFIND Warehouse']);
        Warehouse::factory()->create(['name' => 'Main Warehouse']);

        $response = getJson('/api/warehouses?search=XQZFIND');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.name'))->toBe('XQZFIND Warehouse');
    });

    test('index sorts results', function () {
        Warehouse::factory()->create(['name' => 'AAAA Alpha']);
        Warehouse::factory()->create(['name' => 'ZZZZ Beta']);

        $response = getJson('/api/warehouses?sort_by=name&sort_direction=desc');

        $response->assertOk();
        expect($response->json('data.0.name'))->toBe('ZZZZ Beta');

        $response = getJson('/api/warehouses?sort_by=name&sort_direction=asc');

        $response->assertOk();
        expect($response->json('data.0.name'))->toBe('AAAA Alpha');
    });

    test('store creates warehouse', function () {
        $branch = Branch::factory()->create();
        $data = [
            'branch_id' => $branch->id,
            'code' => 'WH-NEW',
            'name' => 'New Warehouse',
        ];

        $response = postJson('/api/warehouses', $data);

        $response->assertCreated()
            ->assertJsonFragment(['name' => 'New Warehouse']);

        assertDatabaseHas('warehouses', ['name' => 'New Warehouse', 'code' => 'WH-NEW', 'branch_id' => $branch->id]);
    });

    test('store validates unique name', function () {
        $branch = Branch::factory()->create();
        Warehouse::factory()->create(['name' => 'Existing Warehouse']);

        $response = postJson('/api/warehouses', [
            'branch_id' => $branch->id,
            'code' => 'WH-EXIST',
            'name' => 'Existing Warehouse',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    test('index supports filtering by branch', function () {
        $branch1 = Branch::factory()->create(['name' => 'Branch 1']);
        $branch2 = Branch::factory()->create(['name' => 'Branch 2']);

        Warehouse::factory()->create(['branch_id' => $branch1->id]);
        Warehouse::factory()->count(2)->create(['branch_id' => $branch2->id]);

        $response = getJson("/api/warehouses?branch_id={$branch1->id}");

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(1);
    });

    test('index supports sorting by branch name', function () {
        $branchA = Branch::factory()->create(['name' => 'A Branch']);
        $branchB = Branch::factory()->create(['name' => 'B Branch']);

        Warehouse::factory()->create(['branch_id' => $branchA->id, 'name' => 'Warehouse 1']);
        Warehouse::factory()->create(['branch_id' => $branchB->id, 'name' => 'Warehouse 2']);

        $response = getJson('/api/warehouses?sort_by=branch&sort_direction=asc');

        $response->assertOk();
        expect($response->json('data.0.branch.name'))->toBe('A Branch');
    });

    test('update modifies warehouse', function () {
        $warehouse = Warehouse::factory()->create();
        $data = [
            'name' => 'Updated Warehouse',
        ];

        $response = putJson("/api/warehouses/{$warehouse->id}", $data);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Updated Warehouse']);

        assertDatabaseHas('warehouses', ['id' => $warehouse->id, 'name' => 'Updated Warehouse']);
    });

    test('destroy removes warehouse', function () {
        $warehouse = Warehouse::factory()->create();

        $response = deleteJson("/api/warehouses/{$warehouse->id}");

        $response->assertNoContent();
        assertDatabaseMissing('warehouses', ['id' => $warehouse->id]);
    });
});
