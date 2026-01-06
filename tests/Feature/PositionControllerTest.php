<?php

use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

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

test('index filters positions by search term', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Position::factory()->create(['name' => 'Manager']);
    Position::factory()->create(['name' => 'Developer']);
    Position::factory()->create(['name' => 'Designer']);

    $response = getJson('/api/positions?search=dev');

    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['name'])->toBe('Developer');
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

test('export returns excel file url and filename', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create some positions to export
    Position::factory()->count(3)->create();

    $response = postJson('/api/positions/export', []);

    $response->assertOk()
        ->assertJsonStructure([
            'url',
            'filename',
        ]);

    $data = $response->json();
    expect($data['url'])->toContain('storage/exports/');
    expect($data['filename'])->toContain('positions_export_');
    expect($data['filename'])->toContain('.xlsx');
});
