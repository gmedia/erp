<?php

use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('product-categories');

describe('ProductCategory API Endpoints', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions([
            'product_category',
            'product_category.create',
            'product_category.edit',
            'product_category.delete'
        ]);

        actingAs($user);
    });

    test('index returns paginated product categories', function () {
        ProductCategory::factory()->count(15)->create();

        $response = getJson('/api/product-categories?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'description', 'created_at', 'updated_at']
                ],
                'meta' => ['total', 'per_page', 'current_page']
            ]);

        expect($response->json('meta.total'))->toBe(15)
            ->and($response->json('data'))->toHaveCount(10);
    });

    test('index supports search filtering', function () {
        ProductCategory::factory()->create(['name' => 'Electronics']);
        ProductCategory::factory()->create(['name' => 'Furniture']);

        $response = getJson('/api/product-categories?search=Electron');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.name'))->toBe('Electronics');
    });

    test('index sorts results', function () {
        ProductCategory::factory()->create(['name' => 'AAAA Alpha']);
        ProductCategory::factory()->create(['name' => 'ZZZZ Beta']);

        $response = getJson('/api/product-categories?sort_by=name&sort_direction=desc');

        $response->assertOk();
        expect($response->json('data.0.name'))->toBe('ZZZZ Beta');
        
        $response = getJson('/api/product-categories?sort_by=name&sort_direction=asc');
        
        $response->assertOk();
        expect($response->json('data.0.name'))->toBe('AAAA Alpha');
    });

    test('store creates product category', function () {
        $data = [
            'name' => 'New Category',
            'description' => 'Category Description'
        ];

        $response = postJson('/api/product-categories', $data);

        $response->assertCreated()
            ->assertJsonFragment(['name' => 'New Category', 'description' => 'Category Description']);

        assertDatabaseHas('product_categories', ['name' => 'New Category']);
    });

    test('store validates unique name', function () {
        ProductCategory::factory()->create(['name' => 'Existing Cat']);

        $response = postJson('/api/product-categories', [
            'name' => 'Existing Cat',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    test('update modifies product category', function () {
        $category = ProductCategory::factory()->create();
        $data = [
            'name' => 'Updated Cat',
            'description' => 'Updated Description'
        ];

        $response = putJson("/api/product-categories/{$category->id}", $data);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Updated Cat', 'description' => 'Updated Description']);

        assertDatabaseHas('product_categories', ['id' => $category->id, 'name' => 'Updated Cat']);
    });

    test('destroy removes product category', function () {
        $category = ProductCategory::factory()->create();

        $response = deleteJson("/api/product-categories/{$category->id}");

        $response->assertNoContent();
        assertDatabaseMissing('product_categories', ['id' => $category->id]);
    });
});
