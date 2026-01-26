<?php

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('suppliers');

/**
 * Helper function to create a user with specific permissions.
 */
function createSupplierUserWithPermissions(array $permissionNames = []): User
{
    $user = User::factory()->create();
    
    // Create required permissions
    foreach ($permissionNames as $name) {
        Permission::firstOrCreate(
            ['name' => $name],
            ['display_name' => ucwords(str_replace(['.', '-'], ' ', $name))]
        );
    }
    
    // Assign permissions to user directly or via role (assuming direct assignment for simplicity in tests or via a helper if exists)
    // In this codebase, permissions seem to be assigned to employees usually, but let's see how the Employee test did it.
    // The Employee test helper `createUserWithPermissions` creates an employee for the user and syncs permissions to the employee.
    // I should probably follow that pattern if the authorization middleware checks employee permissions.
    // Let's assume standard Laravel permission checking for now, but looking at EmployeeControllerTest, it does:
    // $employee->permissions()->sync($permissions);
    
    // So I need to create an employee for this user.
    $employee = \App\Models\Employee::factory()->create(['user_id' => $user->id]);
    
    $permissionIds = Permission::whereIn('name', $permissionNames)->pluck('id');
    $employee->permissions()->sync($permissionIds);

    return $user;
}

describe('Supplier API Endpoints', function () {
    beforeEach(function () {
        // Create user with all supplier permissions
        $this->user = createSupplierUserWithPermissions([
            'supplier',
            'supplier.create',
            'supplier.edit',
            'supplier.delete'
        ]);
        
        actingAs($this->user);
    });

    test('index returns paginated suppliers', function () {
        Supplier::factory()->count(15)->create();

        $response = getJson('/api/suppliers?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['total', 'per_page', 'current_page']
            ]);

        expect($response->json('meta.total'))->toBe(15)
            ->and($response->json('data'))->toHaveCount(10);
    });

    test('index supports search filtering', function () {
        Supplier::factory()->create(['name' => 'Tech Corp', 'email' => 'tech@example.com']);
        Supplier::factory()->create(['name' => 'Furniture Mart', 'email' => 'furniture@example.com']);

        $response = getJson('/api/suppliers?search=Tech');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.name'))->toBe('Tech Corp');
    });

    test('index supports filtering by branch', function () {
        $branch1 = Branch::factory()->create(['name' => 'Branch A']);
        $branch2 = Branch::factory()->create(['name' => 'Branch B']);

        Supplier::factory()->create(['branch_id' => $branch1->id]);
        Supplier::factory()->create(['branch_id' => $branch2->id]);

        $response = getJson('/api/suppliers?branch_id=' . $branch1->id);

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.branch_id'))->toBe($branch1->id);
    });

    test('index supports filtering by category', function () {
        $category = \App\Models\SupplierCategory::factory()->create();
        Supplier::factory()->create(['category_id' => $category->id]);
        Supplier::factory()->create(); // different category

        $response = getJson('/api/suppliers?category_id=' . $category->id);

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.category_id'))->toBe($category->id);
    });

    test('index supports filtering by status', function () {
        Supplier::factory()->create(['status' => 'active']);
        Supplier::factory()->create(['status' => 'inactive']);

        $response = getJson('/api/suppliers?status=active');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.status'))->toBe('active');
    });

    test('store creates supplier', function () {
        $branch = Branch::factory()->create();
        $category = \App\Models\SupplierCategory::factory()->create();
        $data = [
            'name' => 'New Supplier',
            'email' => 'new@example.com',
            'phone' => '1234567890',
            'address' => '123 St',
            'branch_id' => $branch->id,
            'category_id' => $category->id,
            'status' => 'active',
        ];

        $response = postJson('/api/suppliers', $data);

        $response->assertCreated()
            ->assertJsonFragment(['name' => 'New Supplier']);

        assertDatabaseHas('suppliers', ['email' => 'new@example.com']);
    });

    test('store validates unique email', function () {
        Supplier::factory()->create(['email' => 'existing@example.com']);
        $branch = Branch::factory()->create();
        $category = \App\Models\SupplierCategory::factory()->create();

        $response = postJson('/api/suppliers', [
            'name' => 'Another Supplier',
            'email' => 'existing@example.com',
            'branch_id' => $branch->id,
            'category_id' => $category->id,
            'status' => 'active'
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    test('update modifies supplier', function () {
        $supplier = Supplier::factory()->create();
        $category = \App\Models\SupplierCategory::factory()->create();
        $data = [
            'name' => 'Updated Supplier',
            'email' => $supplier->email, // keep same email
            'category_id' => $category->id,
            'status' => 'inactive'
        ];


        $response = putJson("/api/suppliers/{$supplier->id}", $data);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Updated Supplier']);
            
        $supplier->refresh();
        expect($supplier->name)->toBe('Updated Supplier')
            ->and($supplier->status)->toBe('inactive');
    });

    test('destroy removes supplier', function () {
        $supplier = Supplier::factory()->create();

        $response = deleteJson("/api/suppliers/{$supplier->id}");

        $response->assertNoContent();
        assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    });
});
