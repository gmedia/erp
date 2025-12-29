<?php

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);

test('index returns paginated employees with meta', function () {
    // Create a user and authenticate
    $user = User::factory()->create();
    $this->actingAs($user);

    // Seed some employees
    Employee::factory()->count(15)->create();

    $response = getJson('/api/employees');

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => [
                'current_page',
                'from',
                'last_page',
                'per_page',
                'to',
                'total',
            ],
        ]);

    // Ensure pagination returns 10 items by default (Laravel default)
    expect($response->json('data'))->toHaveCount(15);
});

test('store creates a new employee and returns 201', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $payload = [
        'name' => 'John Doe',
        'email' => 'john.doe@example.com',
        'phone' => '555-1234',
        'department' => 'engineering',
        'position' => 'Developer',
        'salary' => 75000.00,
        'hire_date' => '2023-01-15',
    ];

    $response = postJson('/api/employees', $payload);

    $response->assertCreated()
        ->assertJsonFragment(['email' => 'john.doe@example.com']);

    assertDatabaseHas('employees', ['email' => 'john.doe@example.com']);
});

test('show returns a single employee', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $employee = Employee::factory()->create();

    $response = getJson("/api/employees/{$employee->id}");

    $response->assertOk()
        ->assertJsonFragment(['id' => $employee->id]);
});

test('update modifies an employee and returns the updated record', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $employee = Employee::factory()->create([
        'position' => 'Junior Developer',
    ]);

    $payload = [
        'position' => 'Senior Developer',
    ];

    $response = putJson("/api/employees/{$employee->id}", $payload);

    $response->assertOk()
        ->assertJsonFragment(['position' => 'Senior Developer']);

    $employee->refresh();
    expect($employee->position)->toBe('Senior Developer');
});

test('destroy deletes an employee and returns 204', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $employee = Employee::factory()->create();

    $response = deleteJson("/api/employees/{$employee->id}");

    $response->assertNoContent();

    assertDatabaseMissing('employees', ['id' => $employee->id]);
});
