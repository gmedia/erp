<?php

namespace Tests\Feature\Pipelines;

use App\Models\Pipeline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class)->group('pipelines');

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('can list pipelines', function () {
    Pipeline::factory()->count(3)->create();

    $response = $this->actingAs($this->user)->getJson('/api/pipelines');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'code', 'entity_type', 'version', 'is_active', 'created_by']
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total']
        ]);
});

it('can filter pipelines by search', function () {
    Pipeline::factory()->create(['name' => 'UniqueName123']);
    Pipeline::factory()->create(['name' => 'OtherName']);

    $response = $this->actingAs($this->user)->getJson('/api/pipelines?search=UniqueName123');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.name'))->toBe('UniqueName123');
});

it('can filter pipelines by entity_type', function () {
    Pipeline::factory()->create(['entity_type' => 'App\\Models\\Asset']);
    Pipeline::factory()->create(['entity_type' => 'App\\Models\\PurchaseOrder']);

    $response = $this->actingAs($this->user)->getJson('/api/pipelines?entity_type=App\Models\Asset');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.entity_type'))->toBe('App\\Models\\Asset');
});

it('can show a pipeline', function () {
    $pipeline = Pipeline::factory()->create();

    $response = $this->actingAs($this->user)->getJson("/api/pipelines/{$pipeline->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $pipeline->id)
        ->assertJsonPath('data.name', $pipeline->name);
});

it('can create a pipeline', function () {
    $payload = [
        'name' => 'Test Pipeline',
        'code' => 'test_pipeline',
        'entity_type' => 'App\\Models\\Asset',
        'is_active' => true,
    ];

    $response = $this->actingAs($this->user)->postJson('/api/pipelines', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'Test Pipeline');

    $this->assertDatabaseHas('pipelines', [
        'code' => 'test_pipeline',
        'created_by' => $this->user->id,
    ]);
});

it('validates unique code when creating a pipeline', function () {
    Pipeline::factory()->create(['code' => 'existing_code']);

    $payload = [
        'name' => 'New Pipeline',
        'code' => 'existing_code',
        'entity_type' => 'App\\Models\\Asset',
    ];

    $response = $this->actingAs($this->user)->postJson('/api/pipelines', $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

it('can update a pipeline', function () {
    $pipeline = Pipeline::factory()->create(['name' => 'Old Name', 'code' => 'old_code']);

    $payload = [
        'name' => 'Updated Name',
        'code' => 'updated_code',
        'entity_type' => $pipeline->entity_type, 
        'is_active' => false,
    ];

    $response = $this->actingAs($this->user)->putJson("/api/pipelines/{$pipeline->id}", $payload);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', 'Updated Name');

    $this->assertDatabaseHas('pipelines', [
        'id' => $pipeline->id,
        'name' => 'Updated Name',
        'code' => 'updated_code',
        'is_active' => 0,
    ]);
});

it('ignores unique validation for its own code during update', function () {
    $pipeline = Pipeline::factory()->create(['code' => 'my_code']);

    $payload = [
        'name' => 'Updated Name',
        'code' => 'my_code',
        'entity_type' => 'App\\Models\\Asset',
    ];

    $response = $this->actingAs($this->user)->putJson("/api/pipelines/{$pipeline->id}", $payload);

    $response->assertStatus(200);
});

it('can delete a pipeline', function () {
    $pipeline = Pipeline::factory()->create();

    $response = $this->actingAs($this->user)->deleteJson("/api/pipelines/{$pipeline->id}");

    $response->assertStatus(204);

    $this->assertDatabaseMissing('pipelines', ['id' => $pipeline->id]);
});
