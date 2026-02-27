<?php

namespace Tests\Feature\Pipelines;

use App\Models\Pipeline;
use App\Models\PipelineState;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('pipelines', 'pipeline-states');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['pipeline', 'pipeline.edit']);
});

it('can list pipeline states for a pipeline', function () {
    $pipeline = Pipeline::factory()->create();
    PipelineState::factory()->count(3)->create(['pipeline_id' => $pipeline->id]);
    // Create some for another pipeline to ensure isolation
    PipelineState::factory()->count(2)->create();

    $response = $this->actingAs($this->user)->getJson("/api/pipelines/{$pipeline->id}/states");

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

it('can create a pipeline state', function () {
    $pipeline = Pipeline::factory()->create();

    $payload = [
        'code' => 'new_state',
        'name' => 'New State',
        'type' => 'intermediate',
        'color' => '#ffffff',
        'icon' => 'check',
        'description' => 'A new state',
        'sort_order' => 10,
    ];

    $response = $this->actingAs($this->user)->postJson("/api/pipelines/{$pipeline->id}/states", $payload);

    $response->assertStatus(201)
        ->assertJsonPath('data.code', 'new_state')
        ->assertJsonPath('data.name', 'New State');

    $this->assertDatabaseHas('pipeline_states', [
        'pipeline_id' => $pipeline->id,
        'code' => 'new_state',
        'name' => 'New State',
    ]);
});

it('validates unique code per pipeline when creating a pipeline state', function () {
    $pipeline = Pipeline::factory()->create();
    PipelineState::factory()->create(['pipeline_id' => $pipeline->id, 'code' => 'existing_code']);

    $payload = [
        'code' => 'existing_code',
        'name' => 'New State',
        'type' => 'intermediate',
    ];

    $response = $this->actingAs($this->user)->postJson("/api/pipelines/{$pipeline->id}/states", $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

it('can update a pipeline state', function () {
    $pipeline = Pipeline::factory()->create();
    $state = PipelineState::factory()->create([
        'pipeline_id' => $pipeline->id,
        'name' => 'Old Name',
        'code' => 'old_code',
    ]);

    $payload = [
        'code' => 'new_code',
        'name' => 'Updated Name',
        'type' => 'final',
    ];

    $response = $this->actingAs($this->user)->putJson("/api/pipelines/{$pipeline->id}/states/{$state->id}", $payload);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', 'Updated Name');

    $this->assertDatabaseHas('pipeline_states', [
        'id' => $state->id,
        'name' => 'Updated Name',
        'code' => 'new_code',
    ]);
});

it('allows keeping the same code when updating a pipeline state', function () {
    $pipeline = Pipeline::factory()->create();
    $state = PipelineState::factory()->create([
        'pipeline_id' => $pipeline->id,
        'name' => 'Old Name',
        'code' => 'same_code',
    ]);

    $payload = [
        'code' => 'same_code',
        'name' => 'Updated Name',
        'type' => 'final',
    ];

    $response = $this->actingAs($this->user)->putJson("/api/pipelines/{$pipeline->id}/states/{$state->id}", $payload);

    $response->assertStatus(200);

    $this->assertDatabaseHas('pipeline_states', [
        'id' => $state->id,
        'name' => 'Updated Name',
    ]);
});

it('cannot update a pipeline state belonging to another pipeline', function () {
    $pipeline1 = Pipeline::factory()->create();
    $pipeline2 = Pipeline::factory()->create();
    $state = PipelineState::factory()->create(['pipeline_id' => $pipeline1->id]);

    $payload = [
        'code' => 'new_code',
        'name' => 'Updated Name',
        'type' => 'final',
    ];

    $response = $this->actingAs($this->user)->putJson("/api/pipelines/{$pipeline2->id}/states/{$state->id}", $payload);

    $response->assertStatus(404);
});

it('can delete a pipeline state', function () {
    $pipeline = Pipeline::factory()->create();
    $state = PipelineState::factory()->create(['pipeline_id' => $pipeline->id]);

    $response = $this->actingAs($this->user)->deleteJson("/api/pipelines/{$pipeline->id}/states/{$state->id}");

    $response->assertStatus(204);

    $this->assertDatabaseMissing('pipeline_states', ['id' => $state->id]);
});
