<?php

use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\ApprovalRequest;
use App\Models\Pipeline;
use App\Models\PipelineEntityState;
use App\Models\PipelineState;
use App\Models\PipelineTransition;
use App\Models\PipelineTransitionAction;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('purchase-requests');

/**
 * Build the pr_lifecycle pipeline (draft -> pending_approval -> approved/rejected)
 * and the purchase_request_default approval flow with a single user approver.
 *
 * @return array{approver: User, submit: PipelineTransition, approve: PipelineTransition, reject: PipelineTransition}
 */
function setUpPurchaseRequestApprovalPipeline(): array
{
    $pipeline = Pipeline::factory()->create([
        'code' => 'pr_lifecycle',
        'entity_type' => PurchaseRequest::class,
        'is_active' => true,
    ]);

    $draft = PipelineState::factory()->create([
        'pipeline_id' => $pipeline->id,
        'code' => 'draft',
        'type' => 'initial',
        'sort_order' => 0,
    ]);
    $pending = PipelineState::factory()->create([
        'pipeline_id' => $pipeline->id,
        'code' => 'pending_approval',
        'type' => 'intermediate',
        'sort_order' => 10,
    ]);
    $approved = PipelineState::factory()->create([
        'pipeline_id' => $pipeline->id,
        'code' => 'approved',
        'type' => 'intermediate',
        'sort_order' => 20,
    ]);
    $rejected = PipelineState::factory()->create([
        'pipeline_id' => $pipeline->id,
        'code' => 'rejected',
        'type' => 'intermediate',
        'sort_order' => 30,
    ]);

    $submit = PipelineTransition::factory()->create([
        'pipeline_id' => $pipeline->id,
        'from_state_id' => $draft->id,
        'to_state_id' => $pending->id,
        'name' => 'Submit',
        'code' => 'submit',
        'required_permission' => null,
    ]);
    PipelineTransitionAction::factory()->create([
        'pipeline_transition_id' => $submit->id,
        'action_type' => 'trigger_approval',
        'execution_order' => 5,
        'config' => ['flow_code' => 'purchase_request_default'],
    ]);
    PipelineTransitionAction::factory()->create([
        'pipeline_transition_id' => $submit->id,
        'action_type' => 'update_field',
        'execution_order' => 10,
        'config' => ['field' => 'status', 'value' => 'pending_approval'],
    ]);
    $approve = PipelineTransition::factory()->create([
        'pipeline_id' => $pipeline->id,
        'from_state_id' => $pending->id,
        'to_state_id' => $approved->id,
        'name' => 'Approve',
        'code' => 'approve',
        'required_permission' => null,
    ]);
    $reject = PipelineTransition::factory()->create([
        'pipeline_id' => $pipeline->id,
        'from_state_id' => $pending->id,
        'to_state_id' => $rejected->id,
        'name' => 'Reject',
        'code' => 'reject',
        'required_permission' => null,
    ]);

    $approver = User::factory()->create();

    $flow = ApprovalFlow::factory()->create([
        'approvable_type' => PurchaseRequest::class,
        'code' => 'purchase_request_default',
        'is_active' => true,
        'conditions' => null,
    ]);
    ApprovalFlowStep::factory()->create([
        'approval_flow_id' => $flow->id,
        'step_order' => 1,
        'approver_type' => 'user',
        'approver_user_id' => $approver->id,
    ]);

    return [
        'approver' => $approver,
        'submit' => $submit,
        'approve' => $approve,
        'reject' => $reject,
    ];
}

beforeEach(function () {
    $this->submitter = createTestUserWithPermissions(['pipeline', 'purchase_request.edit']);
});

test('a new purchase request is auto-assigned to the pr_lifecycle pipeline in draft', function () {
    setUpPurchaseRequestApprovalPipeline();
    Sanctum::actingAs($this->submitter, ['*']);

    $purchaseRequest = PurchaseRequest::factory()->create(['status' => 'draft']);

    $entityState = PipelineEntityState::query()
        ->where('entity_type', PurchaseRequest::class)
        ->where('entity_id', $purchaseRequest->id)
        ->first();

    expect($entityState)->not->toBeNull()
        ->and($entityState->currentState->code)->toBe('draft');
});

test('entity-states endpoint exposes the purchase request draft state and Submit transition', function () {
    setUpPurchaseRequestApprovalPipeline();
    Sanctum::actingAs($this->submitter, ['*']);

    $purchaseRequest = PurchaseRequest::factory()->create(['status' => 'draft']);

    getJson("/api/entity-states/purchase_request/{$purchaseRequest->id}")
        ->assertOk()
        ->assertJsonPath('data.current_state.code', 'draft')
        ->assertJsonPath('data.available_transitions.0.name', 'Submit');
});

test('submitting a purchase request creates a pending approval and sets status to pending_approval', function () {
    $setup = setUpPurchaseRequestApprovalPipeline();
    Sanctum::actingAs($this->submitter, ['*']);

    $purchaseRequest = PurchaseRequest::factory()->create(['status' => 'draft']);

    postJson("/api/entity-states/purchase_request/{$purchaseRequest->id}/transition", [
        'transition_id' => $setup['submit']->id,
    ])->assertOk();

    expect($purchaseRequest->fresh()->status)->toBe('pending_approval')
        ->and(ApprovalRequest::query()
            ->where('approvable_type', PurchaseRequest::class)
            ->where('approvable_id', $purchaseRequest->id)
            ->where('status', 'pending')
            ->exists())->toBeTrue();
});

test('approving the final step marks the purchase request approved', function () {
    $setup = setUpPurchaseRequestApprovalPipeline();
    Sanctum::actingAs($this->submitter, ['*']);

    $purchaseRequest = PurchaseRequest::factory()->create(['status' => 'draft']);

    postJson("/api/entity-states/purchase_request/{$purchaseRequest->id}/transition", [
        'transition_id' => $setup['submit']->id,
    ])->assertOk();

    $approvalRequest = ApprovalRequest::query()
        ->where('approvable_type', PurchaseRequest::class)
        ->where('approvable_id', $purchaseRequest->id)
        ->firstOrFail();

    Sanctum::actingAs($setup['approver'], ['*']);
    postJson("/api/my-approvals/{$approvalRequest->id}/approve")->assertOk();

    expect($purchaseRequest->fresh()->status)->toBe('approved')
        ->and($purchaseRequest->fresh()->approved_by)->toBe($setup['approver']->id)
        ->and(purchaseRequestPipelineStateCode($purchaseRequest))->toBe('approved');
});

/**
 * Kode state pipeline pr_lifecycle saat ini untuk satu PR.
 */
function purchaseRequestPipelineStateCode(PurchaseRequest $purchaseRequest): ?string
{
    return PipelineEntityState::query()
        ->where('entity_type', PurchaseRequest::class)
        ->where('entity_id', $purchaseRequest->id)
        ->first()
        ?->currentState?->code;
}

test('rejecting a purchase request marks it rejected', function () {
    $setup = setUpPurchaseRequestApprovalPipeline();
    Sanctum::actingAs($this->submitter, ['*']);

    $purchaseRequest = PurchaseRequest::factory()->create(['status' => 'draft']);

    postJson("/api/entity-states/purchase_request/{$purchaseRequest->id}/transition", [
        'transition_id' => $setup['submit']->id,
    ])->assertOk();

    $approvalRequest = ApprovalRequest::query()
        ->where('approvable_type', PurchaseRequest::class)
        ->where('approvable_id', $purchaseRequest->id)
        ->firstOrFail();

    Sanctum::actingAs($setup['approver'], ['*']);
    postJson("/api/my-approvals/{$approvalRequest->id}/reject", [
        'comments' => 'Anggaran tidak tersedia.',
    ])->assertOk();

    expect($purchaseRequest->fresh()->status)->toBe('rejected')
        ->and(purchaseRequestPipelineStateCode($purchaseRequest))->toBe('rejected');
});

test('manual pipeline Approve transition on a purchase request is rejected with 422', function () {
    $setup = setUpPurchaseRequestApprovalPipeline();
    Sanctum::actingAs($this->submitter, ['*']);

    $purchaseRequest = PurchaseRequest::factory()->create(['status' => 'draft']);

    postJson("/api/entity-states/purchase_request/{$purchaseRequest->id}/transition", [
        'transition_id' => $setup['submit']->id,
    ])->assertOk();

    postJson("/api/entity-states/purchase_request/{$purchaseRequest->id}/transition", [
        'transition_id' => $setup['approve']->id,
    ])->assertUnprocessable();

    expect($purchaseRequest->fresh()->status)->toBe('pending_approval');
});

test('manual pipeline Reject transition on a purchase request is rejected with 422', function () {
    $setup = setUpPurchaseRequestApprovalPipeline();
    Sanctum::actingAs($this->submitter, ['*']);

    $purchaseRequest = PurchaseRequest::factory()->create(['status' => 'draft']);

    postJson("/api/entity-states/purchase_request/{$purchaseRequest->id}/transition", [
        'transition_id' => $setup['submit']->id,
    ])->assertOk();

    postJson("/api/entity-states/purchase_request/{$purchaseRequest->id}/transition", [
        'transition_id' => $setup['reject']->id,
        'comment' => 'manual',
    ])->assertUnprocessable();

    expect($purchaseRequest->fresh()->status)->toBe('pending_approval');
});
