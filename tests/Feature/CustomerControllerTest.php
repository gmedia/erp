<?php

use App\Models\Branch;
use App\Models\Customer;
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

uses(RefreshDatabase::class);

/**
 * Helper function to create a user with an employee that has specific permissions.
 */
function createUserWithCustomerPermissions(array $permissionNames = []): User
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

describe('Customer API Endpoints', function () {
    beforeEach(function () {
        // Create user with all customer permissions for existing tests
        $user = createUserWithCustomerPermissions(['customer', 'customer.create', 'customer.edit', 'customer.delete']);
        actingAs($user);
    });

    test('index returns paginated customers with proper meta structure', function () {
        Customer::factory()->count(25)->create();

        $response = getJson('/api/customers?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'address',
                        'branch' => ['id', 'name'],
                        'customer_type',
                        'status',
                        'notes',
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

    test('index supports search filtering by multiple fields', function () {
        Customer::factory()->create(['name' => 'John Smith', 'email' => 'john@example.com']);
        Customer::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        Customer::factory()->create(['name' => 'Bob Johnson', 'email' => 'bob@example.com']);

        $response = getJson('/api/customers?search=john');

        $response->assertOk();

        $data = $response->json('data');
        expect($data)->toHaveCount(2); // John Smith and Bob Johnson (john in email)
    });

    test('index supports advanced filtering by branch', function () {
        $branchA = Branch::factory()->create(['name' => 'Branch A']);
        $branchB = Branch::factory()->create(['name' => 'Branch B']);

        Customer::factory()->create(['branch_id' => $branchA->id]);
        Customer::factory()->create(['branch_id' => $branchB->id]);
        Customer::factory()->create(['branch_id' => $branchA->id]);

        $response = getJson('/api/customers?branch=' . $branchA->id);

        $response->assertOk();

        $data = $response->json('data');
        expect($data)->toHaveCount(2);
        foreach ($data as $customer) {
            expect($customer['branch']['id'])->toBe($branchA->id);
        }
    });

    test('index supports filtering by customer_type', function () {
        Customer::factory()->create(['customer_type' => 'individual']);
        Customer::factory()->create(['customer_type' => 'company']);
        Customer::factory()->create(['customer_type' => 'individual']);

        $response = getJson('/api/customers?customer_type=company');

        $response->assertOk();

        $data = $response->json('data');
        expect($data)->toHaveCount(1)
            ->and($data[0]['customer_type'])->toBe('company');
    });

    test('index supports filtering by status', function () {
        Customer::factory()->create(['status' => 'active']);
        Customer::factory()->create(['status' => 'inactive']);
        Customer::factory()->create(['status' => 'active']);

        $response = getJson('/api/customers?status=active');

        $response->assertOk();

        $data = $response->json('data');
        expect($data)->toHaveCount(2);
        foreach ($data as $customer) {
            expect($customer['status'])->toBe('active');
        }
    });

    test('index supports sorting by different fields', function () {
        Customer::factory()->create(['name' => 'Z Customer']);
        Customer::factory()->create(['name' => 'A Customer']);

        $response = getJson('/api/customers?sort_by=name&sort_direction=asc');

        $response->assertOk();

        $data = $response->json('data');
        $names = array_column($data, 'name');
        $aIndex = array_search('A Customer', $names);
        $zIndex = array_search('Z Customer', $names);
        expect($aIndex)->toBeLessThan($zIndex);
    });

    test('store creates customer with valid data and returns 201 status', function () {
        $branch = Branch::factory()->create();

        $customerData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '555-1234-5678',
            'address' => '123 Main Street, City, Country',
            'branch' => $branch->id,
            'customer_type' => 'individual',
            'status' => 'active',
            'notes' => 'VIP customer',
        ];

        $response = postJson('/api/customers', $customerData);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'address',
                    'branch',
                    'customer_type',
                    'status',
                    'notes',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJsonFragment([
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'customer_type' => 'individual',
                'status' => 'active',
            ]);

        assertDatabaseHas('customers', [
            'email' => 'john.doe@example.com',
            'name' => 'John Doe'
        ]);
    });

    test('store validates required fields', function () {
        $response = postJson('/api/customers', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'email',
                'address',
                'branch',
                'customer_type',
                'status',
            ]);
    });

    test('store validates unique email constraint', function () {
        Customer::factory()->create(['email' => 'existing@example.com']);
        $branch = Branch::factory()->create();

        $response = postJson('/api/customers', [
            'name' => 'New Customer',
            'email' => 'existing@example.com',
            'address' => '123 Test Street',
            'branch' => $branch->id,
            'customer_type' => 'individual',
            'status' => 'active',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    test('show returns single customer with full resource structure', function () {
        $customer = Customer::factory()->create();

        $response = getJson("/api/customers/{$customer->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'address',
                    'branch',
                    'customer_type',
                    'status',
                    'notes',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJsonFragment([
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email
            ]);
    });

    test('show returns 404 for non-existent customer', function () {
        $response = getJson('/api/customers/99999');

        $response->assertNotFound();
    });

    test('update modifies customer and returns updated resource', function () {
        $branch = Branch::factory()->create();
        $customer = Customer::factory()->create([
            'name' => 'Old Name',
            'branch_id' => $branch->id,
        ]);

        $newBranch = Branch::factory()->create();

        $updateData = [
            'name' => 'Updated Name',
            'email' => $customer->email,
            'address' => $customer->address,
            'branch' => $newBranch->id,
            'customer_type' => 'company',
            'status' => 'active',
        ];

        $response = putJson("/api/customers/{$customer->id}", $updateData);

        $response->assertOk()
            ->assertJsonFragment([
                'name' => 'Updated Name',
                'customer_type' => 'company',
            ]);

        $customer->refresh();
        expect($customer->name)->toBe('Updated Name')
            ->and($customer->branch_id)->toBe($newBranch->id)
            ->and($customer->customer_type)->toBe('company');
    });

    test('update validates fields when provided with invalid data', function () {
        $customer = Customer::factory()->create();

        $response = putJson("/api/customers/{$customer->id}", [
            'name' => '', // Empty name
            'email' => 'invalid-email', // Invalid email format
            'branch' => 'invalid-branch', // Invalid branch
            'customer_type' => 'invalid-type', // Invalid type
            'status' => 'invalid-status', // Invalid status
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'email',
                'branch',
                'customer_type',
                'status',
            ]);
    });

    test('update ignores unique email validation for same customer', function () {
        $branch = Branch::factory()->create();
        $customer = Customer::factory()->create(['email' => 'john@example.com']);

        $response = putJson("/api/customers/{$customer->id}", [
            'name' => 'Updated Name',
            'email' => 'john@example.com', // Same email should be allowed
            'address' => '123 Test Street',
            'branch' => $branch->id,
            'customer_type' => 'individual',
            'status' => 'active',
        ]);

        $response->assertOk();
    });

    test('update returns 404 for non-existent customer', function () {
        $branch = Branch::factory()->create();

        $response = putJson('/api/customers/99999', [
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'address' => '123 Test Street',
            'branch' => $branch->id,
            'customer_type' => 'individual',
            'status' => 'active',
        ]);

        $response->assertNotFound();
    });

    test('destroy removes customer and returns 204 status', function () {
        $customer = Customer::factory()->create();

        $response = deleteJson("/api/customers/{$customer->id}");

        $response->assertNoContent();

        assertDatabaseMissing('customers', ['id' => $customer->id]);
    });

    test('destroy returns 404 for non-existent customer', function () {
        $response = deleteJson('/api/customers/99999');

        $response->assertNotFound();
    });
});

describe('Customer API Permission Tests', function () {
    test('store returns 403 when user lacks customer.create permission', function () {
        $user = createUserWithCustomerPermissions(['customer']);
        actingAs($user);

        $branch = Branch::factory()->create();

        $response = postJson('/api/customers', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'address' => '123 Test Street',
            'branch' => $branch->id,
            'customer_type' => 'individual',
            'status' => 'active',
        ]);

        $response->assertForbidden()
            ->assertJson(['message' => 'You do not have permission to perform this action.']);
    });

    test('update returns 403 when user lacks customer.edit permission', function () {
        $user = createUserWithCustomerPermissions(['customer']);
        actingAs($user);

        $customer = Customer::factory()->create();
        $branch = Branch::factory()->create();

        $response = putJson("/api/customers/{$customer->id}", [
            'name' => 'Updated Name',
            'email' => $customer->email,
            'address' => $customer->address,
            'branch' => $branch->id,
            'customer_type' => 'individual',
            'status' => 'active',
        ]);

        $response->assertForbidden()
            ->assertJson(['message' => 'You do not have permission to perform this action.']);
    });

    test('destroy returns 403 when user lacks customer.delete permission', function () {
        $user = createUserWithCustomerPermissions(['customer']);
        actingAs($user);

        $customer = Customer::factory()->create();

        $response = deleteJson("/api/customers/{$customer->id}");

        $response->assertForbidden()
            ->assertJson(['message' => 'You do not have permission to perform this action.']);
    });
});
