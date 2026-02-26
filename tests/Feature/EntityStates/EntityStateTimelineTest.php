<?php

use App\Models\Asset;
use App\Models\Pipeline;
use App\Models\PipelineEntityState;
use App\Models\PipelineState;
use App\Models\PipelineTransition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;

/**
 * @mixin \Tests\TestCase
 * @property \App\Models\User $user
 * @property \App\Models\Pipeline $pipeline
 * @property \App\Models\PipelineState $stateDraft
 * @property \App\Models\PipelineState $stateReview
 * @property \App\Models\PipelineState $stateActive
 * @property \App\Models\PipelineTransition $transitionSubmit
 * @property \App\Models\PipelineTransition $transitionApprove
 * @property \App\Models\Asset $asset
 */
uses(RefreshDatabase::class)->group('entity-state-timeline');

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
        'color' => '#6B7280',
        'icon' => 'draft-icon'
    ]);

    $this->stateReview = PipelineState::factory()->create([
        'pipeline_id' => $this->pipeline->id,
        'code' => 'review',
        'name' => 'Review',
        'type' => 'intermediate',
        'color' => '#F59E0B',
        'icon' => 'review-icon'
    ]);

    $this->stateActive = PipelineState::factory()->create([
        'pipeline_id' => $this->pipeline->id,
        'code' => 'active',
        'name' => 'Active',
        'type' => 'final',
        'color' => '#10B981',
        'icon' => 'active-icon'
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
        'required_permission' => null, // null means standard user can execute
        'requires_comment' => true,
    ]);

    $this->asset = Asset::factory()->create([
        'status' => 'active'
    ]);
});

it('prevents unauthenticated access', function () {
    $response = $this->getJson("/api/entity-states/asset/{$this->asset->ulid}/timeline");
    $response->assertStatus(401);
});

it('returns 400 for entity that does not support pipelines', function () {
    // Assuming User model does not use Pipeline trait. If it does, find another model.
    // We'll use a mocked class or another known model. Here we use 'user' route.
    $user = \App\Models\User::factory()->create();
    $response = actingAs($this->user)->getJson("/api/entity-states/user/{$user->id}");
    $response->assertStatus(400)
             ->assertJsonPath('message', 'Entity type user does not support pipelines.');
});

it('returns timeline logs for an entity with pipeline state', function () {
    // 1. Trigger initial assignment log
    actingAs($this->user)->getJson("/api/entity-states/asset/{$this->asset->ulid}");

    $response = actingAs($this->user)->getJson("/api/entity-states/asset/{$this->asset->ulid}/timeline");

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.comment', 'Initial pipeline assignment');
});

it('returns timeline logs ordered newest-first', function () {
    actingAs($this->user)->getJson("/api/entity-states/asset/{$this->asset->ulid}");
    
    // add a slight delay or modify created_at to ensure order
    \Carbon\Carbon::setTestNow(now()->addMinute());
    actingAs($this->user)->postJson("/api/entity-states/asset/{$this->asset->ulid}/transition", [
        'transition_id' => $this->transitionSubmit->id,
        'comment' => 'Transition 1',
    ]);

    $response = actingAs($this->user)->getJson("/api/entity-states/asset/{$this->asset->ulid}/timeline");

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data');
    
    // Index 0 should be the newest log (the transition)
    expect($response->json('data.0.comment'))->toBe('Transition 1')
          ->and($response->json('data.1.comment'))->toBe('Initial pipeline assignment');
});

it('has correct content for initial assignment log', function () {
    actingAs($this->user)->getJson("/api/entity-states/asset/{$this->asset->ulid}");

    $response = actingAs($this->user)->getJson("/api/entity-states/asset/{$this->asset->ulid}/timeline");

    $response->assertStatus(200);
    $log = $response->json('data.0');
    
    expect($log['from_state'])->toBeNull()
          ->and($log['to_state']['name'])->toBe('Draft')
          ->and($log['to_state']['color'])->toBe('#6B7280')
          ->and($log['to_state']['icon'])->toBe('draft-icon')
          ->and($log['transition'])->toBeNull()
          ->and($log['comment'])->toBe('Initial pipeline assignment');
});

it('has correct content for transition log', function () {
    actingAs($this->user)->getJson("/api/entity-states/asset/{$this->asset->ulid}");
    
    \Carbon\Carbon::setTestNow(now()->addMinute());
    actingAs($this->user)->postJson("/api/entity-states/asset/{$this->asset->ulid}/transition", [
        'transition_id' => $this->transitionSubmit->id,
        'comment' => 'Moving to review',
    ]);

    $response = actingAs($this->user)->getJson("/api/entity-states/asset/{$this->asset->ulid}/timeline");

    $response->assertStatus(200);
    $log = $response->json('data.0'); // The transition log (newest)
    
    expect($log['from_state']['name'])->toBe('Draft')
          ->and($log['to_state']['name'])->toBe('Review')
          ->and($log['transition']['name'])->toBe('Submit for Review')
          ->and($log['comment'])->toBe('Moving to review')
          ->and($log['performed_by']['name'])->toBe($this->user->name);
});

it('paginates timeline results', function () {
    // Setup initial state
    actingAs($this->user)->getJson("/api/entity-states/asset/{$this->asset->ulid}");

    // Instead of executing 20 transitions via API which requires states, 
    // we just insert logs directly to test pagination.
    $entityState = PipelineEntityState::where('entity_id', $this->asset->id)->first();
    
    \App\Models\PipelineStateLog::factory()->count(20)->create([
        'pipeline_entity_state_id' => $entityState->id,
        'entity_type' => App\Models\Asset::class,
        'entity_id' => $this->asset->id,
        'to_state_id' => $this->stateDraft->id,
        'comment' => 'Dummy test log',
    ]);

    $response = actingAs($this->user)->getJson("/api/entity-states/asset/{$this->asset->ulid}/timeline");

    $response->assertStatus(200)
        ->assertJsonCount(15, 'data') // Default pagination size is 15
        ->assertJsonStructure([
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            'links' => ['first', 'last', 'prev', 'next']
        ]);
    
    expect($response->json('meta.total'))->toBe(21); // 20 inserted + 1 initial
});

it('returns correct timeline after multiple transitions', function () {
    actingAs($this->user)->getJson("/api/entity-states/asset/{$this->asset->ulid}");
    
    // Transition 1 -> Review
    \Carbon\Carbon::setTestNow(now()->addMinute(1));
    actingAs($this->user)->postJson("/api/entity-states/asset/{$this->asset->ulid}/transition", [
        'transition_id' => $this->transitionSubmit->id,
        'comment' => 'Requesting review',
    ]);

    // Transition 2 -> Active
    \Carbon\Carbon::setTestNow(now()->addMinute(2));
    actingAs($this->user)->postJson("/api/entity-states/asset/{$this->asset->ulid}/transition", [
        'transition_id' => $this->transitionApprove->id,
        'comment' => 'LGTM',
    ]);

    $response = actingAs($this->user)->getJson("/api/entity-states/asset/{$this->asset->ulid}/timeline");

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
    
    // Check elements in order (newest first: approve, submit, initial)
    expect($response->json('data.0.transition.name'))->toBe('Approve')
          ->and($response->json('data.0.to_state.name'))->toBe('Active')
          ->and($response->json('data.0.comment'))->toBe('LGTM')
          
          ->and($response->json('data.1.transition.name'))->toBe('Submit for Review')
          ->and($response->json('data.1.to_state.name'))->toBe('Review')
          ->and($response->json('data.1.comment'))->toBe('Requesting review')
          
          ->and($response->json('data.2.transition'))->toBeNull()
          ->and($response->json('data.2.to_state.name'))->toBe('Draft')
          ->and($response->json('data.2.comment'))->toBe('Initial pipeline assignment');
});
