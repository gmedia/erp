<?php

use App\Models\AssetCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('asset-categories');

describe('Asset Category API Endpoints', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions([
            'asset_category',
            'asset_category.create',
            'asset_category.edit',
            'asset_category.delete'
        ]);

        actingAs($user);
    });

    test('index returns paginated asset categories', function () {
        AssetCategory::factory()->count(15)->create();

        $response = getJson('/api/asset-categories?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'code', 'name', 'useful_life_months_default', 'created_at', 'updated_at']
                ],
                'meta' => ['total', 'per_page', 'current_page']
            ]);

        expect($response->json('data'))->toHaveCount(10);
    });

    test('index supports search filtering by name', function () {
        AssetCategory::factory()->create(['code' => 'SEARCH-NAME-1', 'name' => 'UNIQUE_NAME_FILTER']);
        AssetCategory::factory()->create(['code' => 'SEARCH-NAME-2', 'name' => 'OTHER_NAME']);

        $response = getJson('/api/asset-categories?search=UNIQUE_NAME_FILTER');
        $response->assertOk();
        expect($response->json('data'))->toHaveCount(1);
        $response->assertJsonFragment(['name' => 'UNIQUE_NAME_FILTER']);
    });

    test('index supports search filtering by code', function () {
        AssetCategory::factory()->create(['code' => 'UNIQUE-CODE-FILTER', 'name' => 'SOME_NAME_1']);
        AssetCategory::factory()->create(['code' => 'OTHER-CODE', 'name' => 'SOME_NAME_2']);

        $response = getJson('/api/asset-categories?search=UNIQUE-CODE-FILTER');
        $response->assertOk();
        expect($response->json('data'))->toHaveCount(1);
        $response->assertJsonFragment(['code' => 'UNIQUE-CODE-FILTER']);
    });

    test('store creates asset category', function () {
        $data = [
            'code' => 'STORE-CODE',
            'name' => 'Store Test Category',
            'useful_life_months_default' => 48,
        ];

        $response = postJson('/api/asset-categories', $data);

        $response->assertCreated();
        $response->assertJsonFragment($data);

        assertDatabaseHas('asset_categories', $data);
    });

    test('store validates required fields', function () {
        $response = postJson('/api/asset-categories', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['code', 'name']);
    });

    test('update modifies asset category', function () {
        $category = AssetCategory::factory()->create();
        $data = [
            'code' => 'UPDATE-CODE',
            'name' => 'Updated Name',
            'useful_life_months_default' => 36,
        ];

        $response = putJson("/api/asset-categories/{$category->id}", $data);

        $response->assertOk();
        $response->assertJsonFragment($data);

        assertDatabaseHas('asset_categories', array_merge(['id' => $category->id], $data));
    });

    test('destroy removes asset category', function () {
        $category = AssetCategory::factory()->create();

        $response = deleteJson("/api/asset-categories/{$category->id}");

        $response->assertNoContent();
        assertDatabaseMissing('asset_categories', ['id' => $category->id]);
    });
});
