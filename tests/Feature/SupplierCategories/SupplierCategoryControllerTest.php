<?php

use App\Models\SupplierCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('supplier-categories');

describe('SupplierCategory API Endpoints', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions([
            'supplier_category',
            'supplier_category.create',
            'supplier_category.edit',
            'supplier_category.delete'
        ]);

        actingAs($user);
    });

    test('index returns paginated supplier categories', function () {
        $baseline = SupplierCategory::count();
        SupplierCategory::factory()->count(15)->create();

        $response = getJson('/api/supplier-categories?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'created_at', 'updated_at']
                ],
                'meta' => ['total', 'per_page', 'current_page']
            ]);

        expect($response->json('meta.total'))->toBe($baseline + 15)
            ->and($response->json('data'))->toHaveCount(10);
    });

    test('index supports search filtering', function () {
        SupplierCategory::factory()->create(['name' => 'Raw Materials']);
        SupplierCategory::factory()->create(['name' => 'Services']);

        $response = getJson('/api/supplier-categories?search=Raw');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.name'))->toBe('Raw Materials');
    });

    test('index sorts results', function () {
        SupplierCategory::factory()->create(['name' => 'AAAA Alpha']);
        SupplierCategory::factory()->create(['name' => 'ZZZZ Beta']);

        $response = getJson('/api/supplier-categories?sort_by=name&sort_direction=desc');

        $response->assertOk();
        expect($response->json('data.0.name'))->toBe('ZZZZ Beta');
        
        $response = getJson('/api/supplier-categories?sort_by=name&sort_direction=asc');
        
        $response->assertOk();
        expect($response->json('data.0.name'))->toBe('AAAA Alpha');
    });

    test('store creates supplier category', function () {
        $data = [
            'name' => 'New Category',
        ];

        $response = postJson('/api/supplier-categories', $data);

        $response->assertCreated()
            ->assertJsonFragment(['name' => 'New Category']);

        assertDatabaseHas('supplier_categories', ['name' => 'New Category']);
    });

    test('store validates unique name', function () {
        SupplierCategory::factory()->create(['name' => 'Existing Cat']);

        $response = postJson('/api/supplier-categories', [
            'name' => 'Existing Cat',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    test('update modifies supplier category', function () {
        $category = SupplierCategory::factory()->create();
        $data = [
            'name' => 'Updated Cat',
        ];

        $response = putJson("/api/supplier-categories/{$category->id}", $data);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Updated Cat']);

        assertDatabaseHas('supplier_categories', ['id' => $category->id, 'name' => 'Updated Cat']);
    });

    test('destroy removes supplier category', function () {
        $category = SupplierCategory::factory()->create();

        $response = deleteJson("/api/supplier-categories/{$category->id}");

        $response->assertNoContent();
        assertDatabaseMissing('supplier_categories', ['id' => $category->id]);
    });
});
