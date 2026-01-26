<?php

use App\Models\SupplierCategory;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('supplier_categories');

/**
 * Helper function to create a user with an employee that has specific permissions.
 */
function createUserWithSupplierCategoryPermissions(array $permissionNames = []): User
{
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    if (!empty($permissionNames)) {
        $permissions = [];
        foreach ($permissionNames as $name) {
            $permissions[] = Permission::firstOrCreate(
                ['name' => $name],
                ['display_name' => ucwords(str_replace('.', ' ', $name))]
            )->id;
        }
        $employee->permissions()->sync($permissions);
    }

    return $user;
}

describe('SupplierCategory API Endpoints', function () {
    beforeEach(function () {
        // Create user with all supplier category permissions for existing tests
        $user = createUserWithSupplierCategoryPermissions(['supplier_category', 'supplier_category.create', 'supplier_category.edit', 'supplier_category.delete']);
        actingAs($user);
    });

    test('index returns paginated supplier categories with proper meta structure', function () {
        SupplierCategory::factory()->count(25)->create();

        $response = getJson('/api/supplier-categories?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'per_page',
                    'to',
                    'total',
                ],
            ]);

        expect($response->json('meta.total'))->toBe(25)
            ->and($response->json('meta.per_page'))->toBe(10)
            ->and($response->json('data'))->toHaveCount(10);
    });

    test('index supports search filtering by name', function () {
        SupplierCategory::factory()->create(['name' => 'Marketing Category']);
        SupplierCategory::factory()->create(['name' => 'Sales Category']);
        SupplierCategory::factory()->create(['name' => 'Engineering Category']);

        $response = getJson('/api/supplier-categories?search=market');

        $response->assertOk();

        $data = $response->json('data');
        expect($data)->toHaveCount(1)
            ->and($data[0]['name'])->toBe('Marketing Category');
    });

    test('index supports sorting by different fields', function () {
        SupplierCategory::factory()->create(['name' => 'Z Category']);
        SupplierCategory::factory()->create(['name' => 'A Category']);

        $response = getJson('/api/supplier-categories?sort_by=name&sort_direction=asc');

        $response->assertOk();

        $data = $response->json('data');
        $names = array_column($data, 'name');
        $aIndex = array_search('A Category', $names);
        $zIndex = array_search('Z Category', $names);
        expect($aIndex)->toBeLessThan($zIndex);
    });

    test('store creates supplier category with valid data and returns 201 status', function () {
        $data = ['name' => 'Test Category'];

        $response = postJson('/api/supplier-categories', $data);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJsonFragment(['name' => 'Test Category']);

        assertDatabaseHas('supplier_categories', ['name' => 'Test Category']);
    });

    test('store validates required fields', function () {
        $response = postJson('/api/supplier-categories', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    test('store validates unique name constraint', function () {
        SupplierCategory::factory()->create(['name' => 'Existing Category']);

        $response = postJson('/api/supplier-categories', ['name' => 'Existing Category']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    test('show returns single supplier category with full resource structure', function () {
        $category = SupplierCategory::factory()->create();

        $response = getJson("/api/supplier-categories/{$category->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJsonFragment(['id' => $category->id, 'name' => $category->name]);
    });

    test('show returns 404 for non-existent supplier category', function () {
        $response = getJson('/api/supplier-categories/99999');

        $response->assertNotFound();
    });

    test('update modifies supplier category and returns updated resource', function () {
        $category = SupplierCategory::factory()->create(['name' => 'Old Name']);
        $updateData = ['name' => 'Updated Category Name'];

        $response = putJson("/api/supplier-categories/{$category->id}", $updateData);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJsonFragment(['name' => 'Updated Category Name']);

        $category->refresh();
        expect($category->name)->toBe('Updated Category Name');
    });

    test('update validates fields when provided with invalid data', function () {
        $category = SupplierCategory::factory()->create();

        $response = putJson("/api/supplier-categories/{$category->id}", [
            'name' => '', // Empty name
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    test('update ignores unique name validation for same supplier category', function () {
        $category = SupplierCategory::factory()->create(['name' => 'Test Category']);

        $response = putJson("/api/supplier-categories/{$category->id}", [
            'name' => 'Test Category', // Same name should be allowed
        ]);

        $response->assertOk();
    });

    test('update validates unique name constraint for different supplier category', function () {
        $category1 = SupplierCategory::factory()->create(['name' => 'Category One']);
        $category2 = SupplierCategory::factory()->create(['name' => 'Category Two']);

        $response = putJson("/api/supplier-categories/{$category1->id}", [
            'name' => 'Category Two', // Name from different category should fail
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    test('update returns 404 for non-existent supplier category', function () {
        $response = putJson('/api/supplier-categories/99999', ['name' => 'Test']);

        $response->assertNotFound();
    });

    test('destroy removes supplier category and returns 204 status', function () {
        $category = SupplierCategory::factory()->create();

        $response = deleteJson("/api/supplier-categories/{$category->id}");

        $response->assertNoContent();

        assertDatabaseMissing('supplier_categories', ['id' => $category->id]);
    });

    test('destroy returns 404 for non-existent supplier category', function () {
        $response = deleteJson('/api/supplier-categories/99999');

        $response->assertNotFound();
    });

    test('export generates excel file and returns proper response structure', function () {
        SupplierCategory::factory()->count(5)->create();

        $response = postJson('/api/supplier-categories/export', []);

        $response->assertOk()
            ->assertJsonStructure([
                'url',
                'filename',
            ]);

        $data = $response->json();
        expect($data['url'])->toContain('storage/exports/')
            ->and($data['filename'])->toContain('supplier_categories_export_')
            ->and($data['filename'])->toContain('.xlsx')
            ->and($data['filename'])->toMatch('/supplier_categories_export_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.xlsx/');
    });

    test('export applies search filter correctly', function () {
        SupplierCategory::factory()->create(['name' => 'Manager']);
        SupplierCategory::factory()->create(['name' => 'Developer']);
        SupplierCategory::factory()->create(['name' => 'Designer']);

        $response = postJson('/api/supplier-categories/export', ['search' => 'dev']);

        $response->assertOk();

        expect($response->json())->toHaveKeys(['url', 'filename']);
    });
});

describe('SupplierCategory API Permission Tests', function () {
    test('store returns 403 when user lacks supplier_category.create permission', function () {
        $user = createUserWithSupplierCategoryPermissions(['supplier_category']);
        actingAs($user);

        $response = postJson('/api/supplier-categories', ['name' => 'Test Category']);

        $response->assertForbidden()
            ->assertJson(['message' => 'You do not have permission to perform this action.']);
    });

    test('update returns 403 when user lacks supplier_category.edit permission', function () {
        $user = createUserWithSupplierCategoryPermissions(['supplier_category']);
        actingAs($user);

        $category = SupplierCategory::factory()->create();

        $response = putJson("/api/supplier-categories/{$category->id}", ['name' => 'Updated Name']);

        $response->assertForbidden()
            ->assertJson(['message' => 'You do not have permission to perform this action.']);
    });

    test('destroy returns 403 when user lacks supplier_category.delete permission', function () {
        $user = createUserWithSupplierCategoryPermissions(['supplier_category']);
        actingAs($user);

        $category = SupplierCategory::factory()->create();

        $response = deleteJson("/api/supplier-categories/{$category->id}");

        $response->assertForbidden()
            ->assertJson(['message' => 'You do not have permission to perform this action.']);
    });
});
