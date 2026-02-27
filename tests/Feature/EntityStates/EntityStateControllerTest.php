<?php

use App\Models\Asset;
use App\Models\Pipeline;
use App\Models\PipelineEntityState;
use App\Models\PipelineState;
use App\Models\PipelineStateLog;
use App\Models\PipelineTransition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('entity-state-actions');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['pipeline']);
    
    // Create pipeline setup
    $this->pipeline = Pipeline::factory()->create([
        'entity_type' => App\Models\Asset::class,
        'is_active' => true,
    ]);

    $this->stateDraft = PipelineState::factory()->create([
        'pipeline_id' => $this->pipeline->id,
        'code' => 'draft',
        'name' => 'Draft',
        'type' => 'initial',
    ]);

    $this->stateReview = PipelineState::factory()->create([
        'pipeline_id' => $this->pipeline->id,
        'code' => 'review',
        'name' => 'Review',
        'type' => 'intermediate',
    ]);

    $this->stateActive = PipelineState::factory()->create([
        'pipeline_id' => $this->pipeline->id,
        'code' => 'active',
        'name' => 'Active',
        'type' => 'final',
    ]);

    $this->transitionSubmit = PipelineTransition::factory()->create([
        'pipeline_id' => $this->pipeline->id,
        'from_state_id' => $this->stateDraft->id,
        'to_state_id' => $this->stateReview->id,
        'name' => 'Submit for Review',
    ]);

    $this->transitionApprove = PipelineTransition::factory()->create([
        'pipeline_id' => $this->pipeline->id,
        'from_state_id' => $this->stateReview->id,
        'to_state_id' => $this->stateActive->id,
        'name' => 'Approve',
        'required_permission' => 'asset.approve',
        'requires_comment' => true,
        'guard_conditions' => [
            'field_checks' => [
                ['field' => 'status', 'operator' => 'equals', 'value' => 'active']
            ]
        ]
    ]);

    $this->asset = Asset::factory()->create([
        'status' => 'active'
    ]);
});

it('can assign initial pipeline state and get state', function () {
    // Making the request will run AssignPipelineAction
    $response = actingAs($this->user)->getJson("/api/entity-states/asset/{$this->asset->ulid}");

    $response->assertStatus(201)
        ->assertJsonPath('data.entity_id', $this->asset->id)
        ->assertJsonPath('data.current_state.code', 'draft')
        ->assertJsonPath('data.available_transitions.0.name', 'Submit for Review');

    // Verify DB
    $this->assertDatabaseHas('pipeline_entity_states', [
        'entity_type' => 'App\Models\Asset',
        'entity_id' => $this->asset->id,
        'pipeline_id' => $this->pipeline->id,
        'current_state_id' => $this->stateDraft->id,
    ]);

    $this->assertDatabaseHas('pipeline_state_logs', [
        'entity_type' => 'App\Models\Asset',
        'entity_id' => $this->asset->id,
        'to_state_id' => $this->stateDraft->id,
        'comment' => 'Initial pipeline assignment',
    ]);
});

it('can execute transition', function () {
    // Setup initial state
    actingAs($this->user)->getJson("/api/entity-states/asset/{$this->asset->ulid}");

    $response = actingAs($this->user)->postJson("/api/entity-states/asset/{$this->asset->ulid}/transition", [
        'transition_id' => $this->transitionSubmit->id,
        'comment' => 'Ready for review',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Transition executed successfully')
        ->assertJsonPath('data.current_state.code', 'review');

    // Verify DB
    $this->assertDatabaseHas('pipeline_entity_states', [
        'entity_id' => $this->asset->id,
        'current_state_id' => $this->stateReview->id,
    ]);

    $this->assertDatabaseHas('pipeline_state_logs', [
        'entity_id' => $this->asset->id,
        'transition_id' => $this->transitionSubmit->id,
        'comment' => 'Ready for review',
    ]);
});

it('evaluates permissions and guards in available transitions', function () {
    // Assign pipeline and move to Review state manually
    $entityState = PipelineEntityState::create([
        'pipeline_id' => $this->pipeline->id,
        'entity_type' => 'App\Models\Asset',
        'entity_id' => $this->asset->id,
        'current_state_id' => $this->stateReview->id,
    ]);

    // Request state with basic user
    $response = actingAs($this->user)->getJson("/api/entity-states/asset/{$this->asset->ulid}");

    $response->assertStatus(200);
    $transitions = $response->json('data.available_transitions');
    
    // We expect 1 transition (Approve) but it should be disabled due to lack of permission
    $this->assertCount(1, $transitions);
    $this->assertFalse($transitions[0]['is_allowed']);
    $this->assertEquals(['You do not have permission to execute this transition.'], $transitions[0]['rejection_reasons']);

    // Now request with authorized user
    $adminUser = createTestUserWithPermissions(['pipeline', 'asset.approve']);
    $responseAdmin = actingAs($adminUser)->getJson("/api/entity-states/asset/{$this->asset->ulid}");
    
    $transitionsAdmin = $responseAdmin->json('data.available_transitions');
    // It should be allowed because user has permission AND the asset status is 'active' (guard passes)
    dump($transitionsAdmin[0]['rejection_reasons']); $this->assertTrue($transitionsAdmin[0]['is_allowed']);

    // Now change asset so guard fails
    $this->asset->update(['status' => 'maintenance']);
    
    $responseFailedGuard = actingAs($adminUser)->getJson("/api/entity-states/asset/{$this->asset->ulid}");
    $transitionsFailed = $responseFailedGuard->json('data.available_transitions');
    
    $this->assertFalse($transitionsFailed[0]['is_allowed']);
    $this->assertStringContainsString('Field check failed: status must equals', $transitionsFailed[0]['rejection_reasons'][0]);
});

it('rejects execution if guards fail', function () {
    $entityState = PipelineEntityState::create([
        'pipeline_id' => $this->pipeline->id,
        'entity_type' => 'App\Models\Asset',
        'entity_id' => $this->asset->id,
        'current_state_id' => $this->stateReview->id,
    ]);

    $this->asset->update(['status' => 'maintenance']);
    $adminUser = createTestUserWithPermissions(['pipeline', 'asset.approve']);

    $response = actingAs($adminUser)->postJson("/api/entity-states/asset/{$this->asset->ulid}/transition", [
        'transition_id' => $this->transitionApprove->id,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['guards']);
});

it('executes update_field transition action', function () {
    // Add action to the submit transition
    $this->transitionSubmit->actions()->create([
        'action_type' => 'update_field',
        'config' => [
            'field' => 'condition',
            'value' => 'good'
        ],
        'execution_order' => 1,
    ]);

    // Force asset condition to something else first
    $this->asset->update(['condition' => 'needs_repair']);

    actingAs($this->user)->getJson("/api/entity-states/asset/{$this->asset->ulid}");
    
    actingAs($this->user)->postJson("/api/entity-states/asset/{$this->asset->ulid}/transition", [
        'transition_id' => $this->transitionSubmit->id,
    ]);

    // Asset should be updated by the action
    $this->assertEquals('good', $this->asset->fresh()->condition);
});

it('can get timeline', function () {
    actingAs($this->user)->getJson("/api/entity-states/asset/{$this->asset->ulid}");
    actingAs($this->user)->postJson("/api/entity-states/asset/{$this->asset->ulid}/transition", [
        'transition_id' => $this->transitionSubmit->id,
        'comment' => 'First transition',
    ]);

    $response = actingAs($this->user)->getJson("/api/entity-states/asset/{$this->asset->ulid}/timeline");

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data'); // 1 assignment log + 1 transition log
});
