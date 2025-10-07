<?php

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\{getJson, postJson, putJson, deleteJson, assertDatabaseHas, assertDatabaseMissing};

uses(RefreshDatabase::class);

test('index returns paginated departments with meta', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Department::factory()->count(15)->create();

    $response = getJson('/api/departments');

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

    expect($response->json('data'))->toHaveCount(15);
});

test('store creates a new department and returns 201', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $name = fake()->word();

    $payload = [
        'name' => $name,
    ];

    $response = postJson('/api/departments', $payload);

    $response->assertCreated()
        ->assertJsonFragment(['name' => $name]);

    assertDatabaseHas('departments', ['name' => $name]);
});

test('show returns a single department', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $department = Department::factory()->create();

    $response = getJson("/api/departments/{$department->id}");

    $response->assertOk()
        ->assertJsonFragment(['id' => $department->id]);
});

test('update modifies a department and returns the updated record', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $name = fake()->word();

    $department = Department::factory()->create([
        'name' => $name,
    ]);

    $updatedName = fake()->word();

    $payload = [
        'name' => $updatedName,
    ];

    $response = putJson("/api/departments/{$department->id}", $payload);

    $response->assertOk()
        ->assertJsonFragment(['name' => $updatedName]);

    $department->refresh();
    expect($department->name)->toBe($updatedName);
});

test('destroy deletes a department and returns 204', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $department = Department::factory()->create();

    $response = deleteJson("/api/departments/{$department->id}");

    $response->assertNoContent();

    assertDatabaseMissing('departments', ['id' => $department->id]);
});
