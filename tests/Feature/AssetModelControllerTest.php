<?php

use App\Models\AssetCategory;
use App\Models\AssetModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('asset-models');

describe('Asset Model API Endpoints', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions([
            'asset_model',
            'asset_model.create',
            'asset_model.edit',
            'asset_model.delete'
        ]);

        actingAs($user);
    });

    test('index returns paginated asset models', function () {
        AssetModel::query()->delete();
        AssetModel::factory()->count(15)->create();

        $response = getJson('/api/asset-models?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['total', 'per_page', 'current_page']
            ]);

        expect($response->json('meta.total'))->toBe(15)
            ->and($response->json('data'))->toHaveCount(10);
    });

    test('index supports search filtering', function () {
        AssetModel::factory()->create(['model_name' => 'Dell Latitude']);
        AssetModel::factory()->create(['model_name' => 'HP EliteBook']);

        $response = getJson('/api/asset-models?search=Dell');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.model_name'))->toBe('Dell Latitude');
    });

    test('index supports filtering by category', function () {
        $category1 = AssetCategory::factory()->create(['name' => 'Laptops']);
        $category2 = AssetCategory::factory()->create(['name' => 'Monitors']);

        AssetModel::factory()->create(['asset_category_id' => $category1->id]);
        AssetModel::factory()->count(2)->create(['asset_category_id' => $category2->id]);

        $response = getJson("/api/asset-models?asset_category_id={$category1->id}");

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(1);
    });

    test('index supports sorting by category name', function () {
        $categoryA = AssetCategory::factory()->create(['name' => 'AAA Category']);
        $categoryB = AssetCategory::factory()->create(['name' => 'BBB Category']);

        AssetModel::factory()->create(['asset_category_id' => $categoryB->id]);
        AssetModel::factory()->create(['asset_category_id' => $categoryA->id]);

        $response = getJson('/api/asset-models?sort_by=category&sort_direction=asc&per_page=10');

        $response->assertOk();
        expect($response->json('data.0.category.name'))->toBe('AAA Category')
            ->and($response->json('data.1.category.name'))->toBe('BBB Category');
    });

    test('store creates new asset model', function () {
        $category = AssetCategory::factory()->create();

        $data = [
            'model_name' => 'MacBook Pro 16',
            'manufacturer' => 'Apple',
            'asset_category_id' => $category->id,
            'specs' => ['cpu' => 'M3 Pro', 'ram_gb' => 32],
        ];

        $response = postJson('/api/asset-models', $data);

        $response->assertCreated()
            ->assertJsonFragment(['model_name' => 'MacBook Pro 16']);

        assertDatabaseHas('asset_models', [
            'model_name' => 'MacBook Pro 16',
            'manufacturer' => 'Apple',
        ]);
    });

    test('show returns single asset model with category', function () {
        $assetModel = AssetModel::factory()->create();

        $response = getJson("/api/asset-models/{$assetModel->id}");

        $response->assertOk()
            ->assertJsonFragment(['model_name' => $assetModel->model_name])
            ->assertJsonPath('data.category.id', $assetModel->asset_category_id);
    });

    test('update modifies existing asset model', function () {
        $assetModel = AssetModel::factory()->create();

        $response = putJson("/api/asset-models/{$assetModel->id}", [
            'model_name' => 'Updated Model Name',
        ]);

        $response->assertOk();
        expect($assetModel->fresh()->model_name)->toBe('Updated Model Name');
    });

    test('destroy deletes asset model', function () {
        $assetModel = AssetModel::factory()->create();

        $response = deleteJson("/api/asset-models/{$assetModel->id}");

        $response->assertNoContent();
        assertDatabaseMissing('asset_models', ['id' => $assetModel->id]);
    });
});
