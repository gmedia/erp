<?php

use App\Models\Asset;
use App\Models\Pipeline;
use App\Models\PipelineEntityState;
use App\Models\PipelineState;
use App\Models\PipelineTransition;
use App\Models\PipelineTransitionAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;

/**
 * Tests the complete Asset Lifecycle pipeline with all 6 states and 6 transitions.
 *
 * Pipeline: asset_lifecycle
 * States: draft → active → maintenance → disposed / lost / cancelled
 * Transitions: activate, cancel, send_maintenance, return_maintenance, dispose, mark_lost
 */
uses(RefreshDatabase::class)->group('asset-pipeline-lifecycle');

beforeEach(function () {
    $this->user = createTestUserWithPermissions([
        'pipeline',
        'assets.activate',
        'assets.cancel',
        'assets.manage',
        'assets.dispose',
    ]);

    // Create the full Asset Lifecycle pipeline
    $this->pipeline = Pipeline::factory()->create([
        'entity_type' => Asset::class,
        'code' => 'asset_lifecycle',
        'name' => 'Asset Lifecycle',
        'is_active' => true,
    ]);

    // States
    $this->stateDraft = PipelineState::factory()->create([
        'pipeline_id' => $this->pipeline->id,
        'code' => 'draft', 'name' => 'Draft', 'type' => 'initial',
        'color' => '#6B7280', 'icon' => 'FileEdit', 'sort_order' => 0,
    ]);
    $this->stateActive = PipelineState::factory()->create([
        'pipeline_id' => $this->pipeline->id,
        'code' => 'active', 'name' => 'Active', 'type' => 'intermediate',
        'color' => '#10B981', 'icon' => 'CircleCheck', 'sort_order' => 10,
    ]);
    $this->stateMaintenance = PipelineState::factory()->create([
        'pipeline_id' => $this->pipeline->id,
        'code' => 'maintenance', 'name' => 'In Maintenance', 'type' => 'intermediate',
        'color' => '#F59E0B', 'icon' => 'Wrench', 'sort_order' => 20,
    ]);
    $this->stateDisposed = PipelineState::factory()->create([
        'pipeline_id' => $this->pipeline->id,
        'code' => 'disposed', 'name' => 'Disposed', 'type' => 'final',
        'color' => '#EF4444', 'icon' => 'Trash2', 'sort_order' => 30,
    ]);
    $this->stateLost = PipelineState::factory()->create([
        'pipeline_id' => $this->pipeline->id,
        'code' => 'lost', 'name' => 'Lost', 'type' => 'final',
        'color' => '#DC2626', 'icon' => 'AlertTriangle', 'sort_order' => 40,
    ]);
    $this->stateCancelled = PipelineState::factory()->create([
        'pipeline_id' => $this->pipeline->id,
        'code' => 'cancelled', 'name' => 'Cancelled', 'type' => 'final',
        'color' => '#9CA3AF', 'icon' => 'XCircle', 'sort_order' => 50,
    ]);

    // Transitions (with update_field actions)
    $this->transActivate = createTransitionWithAction($this->pipeline, $this->stateDraft, $this->stateActive, 'Activate', 'activate', 'assets.activate', false, false, 'active');
    $this->transCancel = createTransitionWithAction($this->pipeline, $this->stateDraft, $this->stateCancelled, 'Cancel', 'cancel', 'assets.cancel', true, false, 'draft'); // DB enum has no 'cancelled'
    $this->transSendMaint = createTransitionWithAction($this->pipeline, $this->stateActive, $this->stateMaintenance, 'Send to Maintenance', 'send_maintenance', 'assets.manage', false, false, 'maintenance');
    $this->transReturnMaint = createTransitionWithAction($this->pipeline, $this->stateMaintenance, $this->stateActive, 'Return from Maintenance', 'return_maintenance', 'assets.manage', false, false, 'active');
    $this->transDispose = createTransitionWithAction($this->pipeline, $this->stateActive, $this->stateDisposed, 'Dispose', 'dispose', 'assets.dispose', true, true, 'disposed');
    $this->transMarkLost = createTransitionWithAction($this->pipeline, $this->stateActive, $this->stateLost, 'Mark as Lost', 'mark_lost', 'assets.manage', true, true, 'lost');

    $this->asset = Asset::factory()->create(['status' => 'draft']);
});

/**
 * Helper: create transition with an update_field action
 */
function createTransitionWithAction(
    Pipeline $pipeline, PipelineState $from, PipelineState $to,
    string $name, string $code, ?string $permission,
    bool $confirmation, bool $comment,
    string $statusValue
): PipelineTransition {
    $transition = PipelineTransition::factory()->create([
        'pipeline_id' => $pipeline->id,
        'from_state_id' => $from->id,
        'to_state_id' => $to->id,
        'name' => $name,
        'code' => $code,
        'required_permission' => $permission,
        'requires_confirmation' => $confirmation,
        'requires_comment' => $comment,
    ]);

    PipelineTransitionAction::factory()->create([
        'pipeline_transition_id' => $transition->id,
        'action_type' => 'update_field',
        'config' => ['field' => 'status', 'value' => $statusValue],
        'execution_order' => 10,
    ]);

    return $transition;
}

// ── State Assignment ─────────────────────────────────────────────────

it('assigns initial draft state to a new asset', function () {
    $response = actingAs($this->user)->getJson("/api/entity-states/asset/{$this->asset->ulid}");

    $response->assertSuccessful()
        ->assertJsonPath('data.current_state.code', 'draft')
        ->assertJsonPath('data.current_state.name', 'Draft');

    $this->assertDatabaseHas('pipeline_entity_states', [
        'entity_type' => Asset::class,
        'entity_id' => $this->asset->id,
        'current_state_id' => $this->stateDraft->id,
    ]);
});

// ── Transitions ──────────────────────────────────────────────────────

it('can activate: draft → active', function () {
    actingAs($this->user)->getJson("/api/entity-states/asset/{$this->asset->ulid}");

    $response = actingAs($this->user)->postJson("/api/entity-states/asset/{$this->asset->ulid}/transition", [
        'transition_id' => $this->transActivate->id,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.current_state.code', 'active');

    expect($this->asset->fresh()->status)->toBe('active');
});

it('can cancel: draft → cancelled', function () {
    actingAs($this->user)->getJson("/api/entity-states/asset/{$this->asset->ulid}");

    $response = actingAs($this->user)->postJson("/api/entity-states/asset/{$this->asset->ulid}/transition", [
        'transition_id' => $this->transCancel->id,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.current_state.code', 'cancelled');
});

it('can send to maintenance: active → maintenance', function () {
    // Move to active first
    actingAs($this->user)->getJson("/api/entity-states/asset/{$this->asset->ulid}");
    actingAs($this->user)->postJson("/api/entity-states/asset/{$this->asset->ulid}/transition", [
        'transition_id' => $this->transActivate->id,
    ]);

    $response = actingAs($this->user)->postJson("/api/entity-states/asset/{$this->asset->ulid}/transition", [
        'transition_id' => $this->transSendMaint->id,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.current_state.code', 'maintenance');

    expect($this->asset->fresh()->status)->toBe('maintenance');
});

it('can return from maintenance: maintenance → active', function () {
    // Move to active → maintenance
    actingAs($this->user)->getJson("/api/entity-states/asset/{$this->asset->ulid}");
    actingAs($this->user)->postJson("/api/entity-states/asset/{$this->asset->ulid}/transition", [
        'transition_id' => $this->transActivate->id,
    ]);
    actingAs($this->user)->postJson("/api/entity-states/asset/{$this->asset->ulid}/transition", [
        'transition_id' => $this->transSendMaint->id,
    ]);

    $response = actingAs($this->user)->postJson("/api/entity-states/asset/{$this->asset->ulid}/transition", [
        'transition_id' => $this->transReturnMaint->id,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.current_state.code', 'active');

    expect($this->asset->fresh()->status)->toBe('active');
});

it('can dispose: active → disposed with confirmation and comment', function () {
    actingAs($this->user)->getJson("/api/entity-states/asset/{$this->asset->ulid}");
    actingAs($this->user)->postJson("/api/entity-states/asset/{$this->asset->ulid}/transition", [
        'transition_id' => $this->transActivate->id,
    ]);

    $response = actingAs($this->user)->postJson("/api/entity-states/asset/{$this->asset->ulid}/transition", [
        'transition_id' => $this->transDispose->id,
        'comment' => 'Aset sudah melewati masa manfaat, layar rusak.',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.current_state.code', 'disposed');

    expect($this->asset->fresh()->status)->toBe('disposed');

    $this->assertDatabaseHas('pipeline_state_logs', [
        'entity_id' => $this->asset->id,
        'transition_id' => $this->transDispose->id,
        'comment' => 'Aset sudah melewati masa manfaat, layar rusak.',
    ]);
});

it('can mark as lost: active → lost with confirmation and comment', function () {
    actingAs($this->user)->getJson("/api/entity-states/asset/{$this->asset->ulid}");
    actingAs($this->user)->postJson("/api/entity-states/asset/{$this->asset->ulid}/transition", [
        'transition_id' => $this->transActivate->id,
    ]);

    $response = actingAs($this->user)->postJson("/api/entity-states/asset/{$this->asset->ulid}/transition", [
        'transition_id' => $this->transMarkLost->id,
        'comment' => 'Tidak ditemukan setelah pindah kantor.',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.current_state.code', 'lost');

    expect($this->asset->fresh()->status)->toBe('lost');
});

// ── Validation ───────────────────────────────────────────────────────

it('rejects invalid transition from wrong state', function () {
    // Asset is in draft — try to send_maintenance (requires active state)
    actingAs($this->user)->getJson("/api/entity-states/asset/{$this->asset->ulid}");

    $response = actingAs($this->user)->postJson("/api/entity-states/asset/{$this->asset->ulid}/transition", [
        'transition_id' => $this->transSendMaint->id,
    ]);

    $response->assertStatus(422);
});

it('final state has no available transitions', function () {
    // Move to disposed (final)
    actingAs($this->user)->getJson("/api/entity-states/asset/{$this->asset->ulid}");
    actingAs($this->user)->postJson("/api/entity-states/asset/{$this->asset->ulid}/transition", [
        'transition_id' => $this->transActivate->id,
    ]);
    actingAs($this->user)->postJson("/api/entity-states/asset/{$this->asset->ulid}/transition", [
        'transition_id' => $this->transDispose->id,
        'comment' => 'Disposed for test',
    ]);

    $response = actingAs($this->user)->getJson("/api/entity-states/asset/{$this->asset->ulid}");

    $response->assertStatus(200)
        ->assertJsonPath('data.current_state.code', 'disposed');

    $transitions = $response->json('data.available_transitions');
    expect($transitions)->toBeEmpty();
});

// ── Timeline ─────────────────────────────────────────────────────────

it('timeline shows complete lifecycle history after multiple transitions', function () {
    actingAs($this->user)->getJson("/api/entity-states/asset/{$this->asset->ulid}");

    \Carbon\Carbon::setTestNow(now()->addMinute());
    actingAs($this->user)->postJson("/api/entity-states/asset/{$this->asset->ulid}/transition", [
        'transition_id' => $this->transActivate->id,
    ]);

    \Carbon\Carbon::setTestNow(now()->addMinutes(2));
    actingAs($this->user)->postJson("/api/entity-states/asset/{$this->asset->ulid}/transition", [
        'transition_id' => $this->transSendMaint->id,
    ]);

    \Carbon\Carbon::setTestNow(now()->addMinutes(3));
    actingAs($this->user)->postJson("/api/entity-states/asset/{$this->asset->ulid}/transition", [
        'transition_id' => $this->transReturnMaint->id,
    ]);

    $response = actingAs($this->user)->getJson("/api/entity-states/asset/{$this->asset->ulid}/timeline");

    $response->assertStatus(200)
        ->assertJsonCount(4, 'data'); // initial + 3 transitions

    // Newest first
    expect($response->json('data.0.transition.name'))->toBe('Return from Maintenance')
        ->and($response->json('data.0.to_state.name'))->toBe('Active')
        ->and($response->json('data.1.transition.name'))->toBe('Send to Maintenance')
        ->and($response->json('data.1.to_state.name'))->toBe('In Maintenance')
        ->and($response->json('data.2.transition.name'))->toBe('Activate')
        ->and($response->json('data.2.to_state.name'))->toBe('Active')
        ->and($response->json('data.3.comment'))->toBe('Initial pipeline assignment');
});
