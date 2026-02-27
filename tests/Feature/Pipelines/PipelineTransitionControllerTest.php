<?php

use App\Models\Pipeline;
use App\Models\PipelineState;
use App\Models\PipelineTransition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('pipelines');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['pipeline', 'pipeline.create', 'pipeline.edit', 'pipeline.delete']);
    actingAs($this->user);

    $this->pipeline = Pipeline::factory()->create();
    $this->state1 = PipelineState::factory()->create(['pipeline_id' => $this->pipeline->id, 'code' => 'state1']);
    $this->state2 = PipelineState::factory()->create(['pipeline_id' => $this->pipeline->id, 'code' => 'state2']);
    $this->state3 = PipelineState::factory()->create(['pipeline_id' => $this->pipeline->id, 'code' => 'state3']);
});

it('can list pipeline transitions', function () {
    PipelineTransition::factory()->create([
        'pipeline_id' => $this->pipeline->id,
        'from_state_id' => $this->state1->id,
        'to_state_id' => $this->state2->id,
    ]);
    PipelineTransition::factory()->create([
        'pipeline_id' => $this->pipeline->id,
        'from_state_id' => $this->state2->id,
        'to_state_id' => $this->state3->id,
    ]);

    $response = $this->getJson("/api/pipelines/{$this->pipeline->id}/transitions");

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('can create a pipeline transition with actions', function () {
    $data = [
        'from_state_id' => $this->state1->id,
        'to_state_id' => $this->state2->id,
        'name' => 'Move to Next',
        'code' => 'move_next',
        'actions' => [
            [
                'action_type' => 'update_field',
                'execution_order' => 1,
                'config' => ['field' => 'status', 'value' => 'active'],
                'on_failure' => 'abort',
                'is_active' => true,
            ]
        ]
    ];

    $response = $this->postJson("/api/pipelines/{$this->pipeline->id}/transitions", $data);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Move to Next')
        ->assertJsonCount(1, 'data.actions');

    $this->assertDatabaseHas('pipeline_transitions', ['code' => 'move_next']);
    $this->assertDatabaseHas('pipeline_transition_actions', ['action_type' => 'update_field']);
});

it('can update a pipeline transition and sync actions', function () {
    $transition = PipelineTransition::factory()->create([
        'pipeline_id' => $this->pipeline->id,
        'from_state_id' => $this->state1->id,
        'to_state_id' => $this->state2->id,
    ]);
    
    $action = $transition->actions()->create([
        'action_type' => 'update_field',
        'execution_order' => 1,
        'config' => ['initial' => 'value'],
        'on_failure' => 'abort',
        'is_active' => true,
    ]);

    $data = [
        'from_state_id' => $this->state1->id,
        'to_state_id' => $this->state3->id,
        'name' => 'Updated Name',
        'code' => $transition->code,
        'actions' => [
            [
                'id' => $action->id, // Update existing
                'action_type' => 'update_field',
                'execution_order' => 1,
                'config' => ['updated' => true],
                'on_failure' => 'abort',
                'is_active' => true,
            ],
            [
                // Create new
                'action_type' => 'send_notification',
                'execution_order' => 2,
                'config' => ['template' => 'alert'],
                'on_failure' => 'continue',
                'is_active' => true,
            ]
        ]
    ];

    $response = $this->putJson("/api/pipelines/{$this->pipeline->id}/transitions/{$transition->id}", $data);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Updated Name')
        ->assertJsonPath('data.to_state_id', $this->state3->id)
        ->assertJsonCount(2, 'data.actions');

    $this->assertDatabaseHas('pipeline_transition_actions', ['action_type' => 'update_field', 'id' => $action->id]);
    $this->assertDatabaseHas('pipeline_transition_actions', ['action_type' => 'send_notification']);
});

it('can delete a pipeline transition along with its actions', function () {
    $transition = PipelineTransition::factory()->create([
        'pipeline_id' => $this->pipeline->id,
        'from_state_id' => $this->state1->id,
        'to_state_id' => $this->state2->id,
    ]);
    $action = $transition->actions()->create([
        'action_type' => 'update_field',
        'execution_order' => 1,
        'config' => [],
        'on_failure' => 'abort',
    ]);

    $response = $this->deleteJson("/api/pipelines/{$this->pipeline->id}/transitions/{$transition->id}");

    $response->assertNoContent();

    $this->assertDatabaseMissing('pipeline_transitions', ['id' => $transition->id]);
    $this->assertDatabaseMissing('pipeline_transition_actions', ['id' => $action->id]);
});
