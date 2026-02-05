<?php

use App\Models\AssetLocation;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('asset-locations');

describe('Asset Location API Endpoints', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions([
            'asset_location',
            'asset_location.create',
            'asset_location.edit',
            'asset_location.delete'
        ]);

        actingAs($user);
    });

    test('index returns paginated asset locations', function () {
        AssetLocation::factory()->count(15)->create();

        $response = getJson('/api/asset-locations?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['total', 'per_page', 'current_page']
            ]);

        expect($response->json('meta.total'))->toBe(15)
            ->and($response->json('data'))->toHaveCount(10);
    });

    test('index supports search filtering', function () {
        AssetLocation::factory()->create(['name' => 'Warehouse A']);
        AssetLocation::factory()->create(['name' => 'Office B']);

        $response = getJson('/api/asset-locations?search=Warehouse');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.name'))->toBe('Warehouse A');
    });

    test('index supports filtering by branch', function () {
        $branch1 = Branch::factory()->create(['name' => 'Branch 1']);
        $branch2 = Branch::factory()->create(['name' => 'Branch 2']);

        AssetLocation::factory()->create(['branch_id' => $branch1->id]);
        AssetLocation::factory()->count(2)->create(['branch_id' => $branch2->id]);

        $response = getJson("/api/asset-locations?branch_id={$branch1->id}");

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(1);
    });

    test('store creates new asset location', function () {
        $branch = Branch::factory()->create();

        $data = [
            'code' => 'LOC-001',
            'name' => 'Main Warehouse',
            'branch_id' => $branch->id,
            'parent_id' => null,
        ];

        $response = postJson('/api/asset-locations', $data);

        $response->assertCreated()
            ->assertJsonFragment(['name' => 'Main Warehouse']);

        assertDatabaseHas('asset_locations', [
            'code' => 'LOC-001',
            'name' => 'Main Warehouse',
        ]);
    });

    test('store creates asset location with parent', function () {
        $branch = Branch::factory()->create();
        $parent = AssetLocation::factory()->create(['branch_id' => $branch->id]);

        $data = [
            'code' => 'LOC-002',
            'name' => 'Sub Location',
            'branch_id' => $branch->id,
            'parent_id' => $parent->id,
        ];

        $response = postJson('/api/asset-locations', $data);

        $response->assertCreated();
        assertDatabaseHas('asset_locations', [
            'code' => 'LOC-002',
            'parent_id' => $parent->id,
        ]);
    });

    test('show returns single asset location with relations', function () {
        $assetLocation = AssetLocation::factory()->create();

        $response = getJson("/api/asset-locations/{$assetLocation->id}");

        $response->assertOk()
            ->assertJsonFragment(['name' => $assetLocation->name])
            ->assertJsonPath('data.branch.id', $assetLocation->branch_id);
    });

    test('update modifies existing asset location', function () {
        $assetLocation = AssetLocation::factory()->create();

        $response = putJson("/api/asset-locations/{$assetLocation->id}", [
            'name' => 'Updated Location Name',
        ]);

        $response->assertOk();
        expect($assetLocation->fresh()->name)->toBe('Updated Location Name');
    });

    test('destroy deletes asset location', function () {
        $assetLocation = AssetLocation::factory()->create();

        $response = deleteJson("/api/asset-locations/{$assetLocation->id}");

        $response->assertNoContent();
        assertDatabaseMissing('asset_locations', ['id' => $assetLocation->id]);
    });
});
