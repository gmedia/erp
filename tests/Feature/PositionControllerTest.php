<?php

use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\{getJson, postJson, putJson, deleteJson, assertDatabaseHas, assertDatabaseMissing};

uses(RefreshDatabase::class);

test('index returns paginated positions with meta', function () {
    // Create a user and authenticate
    $user = User::factory()->create();
    $this->actingAs($user);

    // Seed some positions
    Position::factory()->count(15)->create();

    $response = getJson('/api/positions');

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

test('store creates a new position and returns 201', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $name = fake()->randomLetter();

    $payload = [
        'name' => $name,
    ];

    $response = postJson('/api/positions', $payload);

    $response->assertCreated()
        ->assertJsonFragment(['name' => $name]);

    assertDatabaseHas('positions', ['name' => $name]);
});

test('show returns a single position', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $position = Position::factory()->create();

    $response = getJson("/api/positions/{$position->id}");

    $response->assertOk()
        ->assertJsonFragment(['id' => $position->id]);
});

test('update modifies an position and returns the updated record', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $name = fake()->randomLetter();

    $position = Position::factory()->create([
        'name' => $name,
    ]);

    $updatedName = fake()->randomLetter();

    $payload = [
        'name' => $updatedName,
    ];

    $response = putJson("/api/positions/{$position->id}", $payload);

    $response->assertOk()
        ->assertJsonFragment(['name' => $updatedName]);

    $position->refresh();
    expect($position->name)->toBe($updatedName);
});

test('destroy deletes an position and returns 204', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $position = Position::factory()->create();

    $response = deleteJson("/api/positions/{$position->id}");

    $response->assertNoContent();

    assertDatabaseMissing('positions', ['id' => $position->id]);
});
