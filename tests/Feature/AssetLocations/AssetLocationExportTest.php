<?php

use App\Models\AssetLocation;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('asset-locations');

describe('Asset Location Export', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions([
            'asset_location',
            'asset_location.create',
        ]);

        actingAs($user);
    });

    test('export returns download url', function () {
        AssetLocation::factory()->count(5)->create();

        $response = $this->postJson('/api/asset-locations/export');

        $response->assertOk()
            ->assertJsonStructure(['url', 'filename']);

        expect($response->json('filename'))->toContain('asset_locations_export_');
    });

    test('export respects search filter', function () {
        AssetLocation::factory()->create(['name' => 'Warehouse A']);
        AssetLocation::factory()->create(['name' => 'Office B']);

        $response = $this->postJson('/api/asset-locations/export', [
            'search' => 'Warehouse',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['url', 'filename']);
    });

    test('export respects branch filter', function () {
        $branch = Branch::factory()->create();
        AssetLocation::factory()->create(['branch_id' => $branch->id]);

        $response = $this->postJson('/api/asset-locations/export', [
            'branch_id' => $branch->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['url', 'filename']);
    });
});
