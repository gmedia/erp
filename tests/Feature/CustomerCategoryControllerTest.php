<?php

use App\Models\CustomerCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('customer-categories');

describe('CustomerCategory API Endpoints', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions([
            'customer_category',
            'customer_category.create',
            'customer_category.edit',
            'customer_category.delete'
        ]);

        actingAs($user);
    });

    test('index returns paginated customer categories', function () {
        CustomerCategory::factory()->count(15)->create();

        $response = getJson('/api/customer-categories?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'created_at', 'updated_at']
                ],
                'meta' => ['total', 'per_page', 'current_page']
            ]);

        expect($response->json('meta.total'))->toBe(15)
            ->and($response->json('data'))->toHaveCount(10);
    });

    test('index supports search filtering', function () {
        CustomerCategory::factory()->create(['name' => 'VIP']);
        CustomerCategory::factory()->create(['name' => 'Regular']);

        $response = getJson('/api/customer-categories?search=VIP');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.name'))->toBe('VIP');
    });

    test('index sorts results', function () {
        CustomerCategory::factory()->create(['name' => 'AAAA Alpha']);
        CustomerCategory::factory()->create(['name' => 'ZZZZ Beta']);

        $response = getJson('/api/customer-categories?sort_by=name&sort_direction=desc');

        $response->assertOk();
        expect($response->json('data.0.name'))->toBe('ZZZZ Beta');
        
        $response = getJson('/api/customer-categories?sort_by=name&sort_direction=asc');
        
        $response->assertOk();
        expect($response->json('data.0.name'))->toBe('AAAA Alpha');
    });

    test('store creates customer category', function () {
        $data = [
            'name' => 'New Category',
        ];

        $response = postJson('/api/customer-categories', $data);

        $response->assertCreated()
            ->assertJsonFragment(['name' => 'New Category']);

        assertDatabaseHas('customer_categories', ['name' => 'New Category']);
    });

    test('store validates unique name', function () {
        CustomerCategory::factory()->create(['name' => 'Existing Cat']);

        $response = postJson('/api/customer-categories', [
            'name' => 'Existing Cat',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    test('update modifies customer category', function () {
        $category = CustomerCategory::factory()->create();
        $data = [
            'name' => 'Updated Cat',
        ];

        $response = putJson("/api/customer-categories/{$category->id}", $data);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Updated Cat']);

        assertDatabaseHas('customer_categories', ['id' => $category->id, 'name' => 'Updated Cat']);
    });

    test('destroy removes customer category', function () {
        $category = CustomerCategory::factory()->create();

        $response = deleteJson("/api/customer-categories/{$category->id}");

        $response->assertNoContent();
        assertDatabaseMissing('customer_categories', ['id' => $category->id]);
    });
});
