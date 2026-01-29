<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('products');

describe('Product API Endpoints', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions(['product', 'product.create', 'product.edit', 'product.delete']);
        actingAs($user);
    });

    test('index returns paginated products with proper structure', function () {
        Product::factory()->count(15)->create();

        $response = getJson('/api/products?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'code', 'name', 'type', 'category', 'unit', 'branch', 'cost', 'selling_price', 'status'
                    ]
                ],
                'meta' => ['total', 'per_page']
            ]);

        expect($response->json('meta.total'))->toBe(15)
            ->and($response->json('data'))->toHaveCount(10);
    });

    test('index supports filtering by category', function () {
        $cat = ProductCategory::factory()->create();
        Product::factory()->create(['category_id' => $cat->id]);
        Product::factory()->create();

        $response = getJson("/api/products?category_id={$cat->id}");

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.category.id'))->toBe($cat->id);
    });

    test('store creates product with valid data', function () {
        $cat = ProductCategory::factory()->create();
        $unit = Unit::factory()->create();

        $data = [
            'code' => 'TEST-001',
            'name' => 'Test Product',
            'type' => 'finished_good',
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'cost' => '500.00',
            'selling_price' => '750.00',
            'status' => 'active',
            'billing_model' => 'one_time',
            'is_recurring' => false,
            'allow_one_time_purchase' => true,
            'is_manufactured' => false,
            'is_purchasable' => true,
            'is_sellable' => true,
            'is_taxable' => true,
        ];

        $response = postJson('/api/products', $data);

        $response->assertCreated();
        assertDatabaseHas('products', [
            'code' => 'TEST-001',
            'name' => 'Test Product',
        ]);
    });

    test('show returns single product', function () {
        $product = Product::factory()->create();

        $response = getJson("/api/products/{$product->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $product->id);
    });

    test('update modifies product data', function () {
        $product = Product::factory()->create(['name' => 'Old Name']);

        $response = putJson("/api/products/{$product->id}", [
            'name' => 'New Name'
        ]);

        $response->assertOk();
        expect($product->fresh()->name)->toBe('New Name');
    });

    test('destroy removes product', function () {
        $product = Product::factory()->create();

        $response = deleteJson("/api/products/{$product->id}");

        $response->assertNoContent();
        assertDatabaseMissing('products', ['id' => $product->id]);
    });

    test('export returns download link', function () {
        Product::factory()->count(5)->create();

        $response = postJson('/api/products/export');

        $response->assertOk()
            ->assertJsonStructure(['url', 'filename']);
    });
});

describe('Product API Permissions', function () {
    test('store returns 403 when lacks permission', function () {
        $user = createTestUserWithPermissions(['product']);
        actingAs($user);

        $response = postJson('/api/products', []);
        $response->assertForbidden();
    });

    test('update returns 403 when lacks permission', function () {
        $user = createTestUserWithPermissions(['product']);
        actingAs($user);
        $product = Product::factory()->create();

        $response = putJson("/api/products/{$product->id}", []);
        $response->assertForbidden();
    });

    test('destroy returns 403 when lacks permission', function () {
        $user = createTestUserWithPermissions(['product']);
        actingAs($user);
        $product = Product::factory()->create();

        $response = deleteJson("/api/products/{$product->id}");
        $response->assertForbidden();
    });
});
