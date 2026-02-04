<?php

use App\Models\AssetCategory;
use App\Models\AssetModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('asset-models');

describe('Asset Model Export', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions([
            'asset_model',
            'asset_model.create',
        ]);

        actingAs($user);
    });

    test('export returns download url', function () {
        AssetModel::factory()->count(5)->create();

        $response = $this->postJson('/api/asset-models/export');

        $response->assertOk()
            ->assertJsonStructure(['url', 'filename']);

        expect($response->json('filename'))->toContain('asset_models_export_');
    });

    test('export respects search filter', function () {
        AssetModel::factory()->create(['model_name' => 'Dell Laptop']);
        AssetModel::factory()->create(['model_name' => 'HP Desktop']);

        $response = $this->postJson('/api/asset-models/export', [
            'search' => 'Dell',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['url', 'filename']);
    });

    test('export respects category filter', function () {
        $category = AssetCategory::factory()->create();
        AssetModel::factory()->create(['asset_category_id' => $category->id]);

        $response = $this->postJson('/api/asset-models/export', [
            'asset_category_id' => $category->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['url', 'filename']);
    });
});
