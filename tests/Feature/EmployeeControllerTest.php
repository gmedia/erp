<?php

use App\Models\Employee;
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

describe('Employee API Endpoints', function () {

    beforeEach(function () {
        $user = User::factory()->create();
        actingAs($user);
    });

    test('index returns paginated employees with proper meta structure', function () {
        Employee::factory()->count(25)->create();

        $response = getJson('/api/employees?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'department',
                        'position',
                        'salary',
                        'hire_date',
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
        Employee::factory()->create(['name' => 'John Smith', 'email' => 'john@example.com']);
        Employee::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        Employee::factory()->create(['name' => 'Bob Johnson', 'email' => 'bob@example.com']);

        $response = getJson('/api/employees?search=john');

        $response->assertOk();

        $data = $response->json('data');
        expect($data)->toHaveCount(2); // John Smith and Bob Johnson (john in email)
    });

    test('index supports advanced filtering by department', function () {
        Employee::factory()->create(['department' => 'Engineering']);
        Employee::factory()->create(['department' => 'Marketing']);
        Employee::factory()->create(['department' => 'Sales']);

        $response = getJson('/api/employees?department=Engineering');

        $response->assertOk();

        $data = $response->json('data');
        expect($data)->toHaveCount(1)
            ->and($data[0]['department'])->toBe('Engineering');
    });

    test('index supports sorting by different fields', function () {
        Employee::factory()->create(['name' => 'Z Employee']);
        Employee::factory()->create(['name' => 'A Employee']);

        $response = getJson('/api/employees?sort_by=name&sort_direction=asc');

        $response->assertOk();

        $data = $response->json('data');
        expect($data[0]['name'])->toBe('A Employee')
            ->and($data[1]['name'])->toBe('Z Employee');
    });

    test('store creates employee with valid data and returns 201 status', function () {
        $employeeData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '555-1234',
            'department' => 'engineering',
            'position' => 'Developer',
            'salary' => '75000.00',
            'hire_date' => '2023-01-15',
        ];

        $response = postJson('/api/employees', $employeeData);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'department',
                    'position',
                    'salary',
                    'hire_date',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJsonFragment([
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'department' => 'engineering',
            ]);

        assertDatabaseHas('employees', [
            'email' => 'john.doe@example.com',
            'name' => 'John Doe'
        ]);
    });

    test('store validates required fields', function () {
        $response = postJson('/api/employees', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'email',
                'department',
                'position',
                'salary',
                'hire_date'
            ]);
    });

    test('store validates unique email constraint', function () {
        Employee::factory()->create(['email' => 'existing@example.com']);

        $response = postJson('/api/employees', [
            'name' => 'New Employee',
            'email' => 'existing@example.com',
            'department' => 'engineering',
            'position' => 'Developer',
            'salary' => '50000.00',
            'hire_date' => '2023-01-01',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    test('show returns single employee with full resource structure', function () {
        $employee = Employee::factory()->create();

        $response = getJson("/api/employees/{$employee->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'department',
                    'position',
                    'salary',
                    'hire_date',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJsonFragment([
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email
            ]);
    });

    test('show returns 404 for non-existent employee', function () {
        $response = getJson('/api/employees/99999');

        $response->assertNotFound();
    });

    test('update modifies employee and returns updated resource', function () {
        $employee = Employee::factory()->create([
            'name' => 'Old Name',
            'position' => 'Junior Developer'
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'position' => 'Senior Developer'
        ];

        $response = putJson("/api/employees/{$employee->id}", $updateData);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'department',
                    'position',
                    'salary',
                    'hire_date',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJsonFragment([
                'name' => 'Updated Name',
                'position' => 'Senior Developer'
            ]);

        $employee->refresh();
        expect($employee->name)->toBe('Updated Name')
            ->and($employee->position)->toBe('Senior Developer');
    });

    test('update validates fields when provided with invalid data', function () {
        $employee = Employee::factory()->create();

        $response = putJson("/api/employees/{$employee->id}", [
            'name' => '', // Empty name
            'email' => 'invalid-email', // Invalid email format
            'department' => 'invalid-dept', // Invalid department
            'salary' => '-100', // Negative salary
            'hire_date' => 'invalid-date', // Invalid date
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'email',
                'department',
                'salary',
                'hire_date'
            ]);
    });

    test('update ignores unique email validation for same employee', function () {
        $employee = Employee::factory()->create(['email' => 'john@example.com']);

        $response = putJson("/api/employees/{$employee->id}", [
            'name' => 'Updated Name',
            'email' => 'john@example.com', // Same email should be allowed
            'department' => 'engineering',
            'position' => 'Developer',
            'salary' => '60000.00',
            'hire_date' => '2023-01-01',
        ]);

        $response->assertOk();
    });

    test('update returns 404 for non-existent employee', function () {
        $response = putJson('/api/employees/99999', [
            'name' => 'Test Employee',
            'email' => 'test@example.com',
            'department' => 'Engineering',
            'position' => 'Developer',
            'salary' => '50000.00',
            'hire_date' => '2023-01-01',
        ]);

        $response->assertNotFound();
    });

    test('destroy removes employee and returns 204 status', function () {
        $employee = Employee::factory()->create();

        $response = deleteJson("/api/employees/{$employee->id}");

        $response->assertNoContent();

        assertDatabaseMissing('employees', ['id' => $employee->id]);
    });

    test('destroy returns 404 for non-existent employee', function () {
        $response = deleteJson('/api/employees/99999');

        $response->assertNotFound();
    });

    test('export generates excel file and returns proper response structure', function () {
        Employee::factory()->count(5)->create();

        $response = postJson('/api/employees/export', []);

        $response->assertOk()
            ->assertJsonStructure([
                'url',
                'filename',
            ]);

        $data = $response->json();
        expect($data['url'])->toContain('storage/exports/')
            ->and($data['filename'])->toContain('employees_export_')
            ->and($data['filename'])->toContain('.xlsx')
            ->and($data['filename'])->toMatch('/employees_export_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.xlsx/');
    });

    test('export applies filters correctly', function () {
        Employee::factory()->create(['department' => 'Engineering']);
        Employee::factory()->create(['department' => 'Marketing']);
        Employee::factory()->create(['position' => 'Manager']);

        $response = postJson('/api/employees/export', [
            'department' => 'Engineering',
            'position' => 'Developer'
        ]);

        $response->assertOk();

        // Note: Actual export verification would require checking the generated file
        // This test verifies the endpoint accepts and processes filters
        expect($response->json())->toHaveKeys(['url', 'filename']);
    });

});
