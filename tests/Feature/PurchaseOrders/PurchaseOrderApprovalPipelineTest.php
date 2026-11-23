<?php

use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\ApprovalRequest;
use App\Models\Pipeline;
use App\Models\PipelineEntityState;
use App\Models\PipelineState;
use App\Models\PipelineTransition;
use App\Models\PipelineTransitionAction;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('purchase-orders');

/**
 * Build the po_lifecycle pipeline (draft -> pending_approval -> confirmed/rejected)
 * and the purchase_order_default approval flow with a single user approver.
 *
 * @return array{approver: User, submit: PipelineTransition, approve: PipelineTransition, reject: PipelineTransition}
 */
function setUpPurchaseOrderApprovalPipeline(): array
{
    $pipeline = Pipeline::factory()->create([
        'code' => 'po_lifecycle',
        'entity_type' => PurchaseOrder::class,
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
    $confirmed = PipelineState::factory()->create([
        'pipeline_id' => $pipeline->id,
        'code' => 'confirmed',
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
        'config' => ['flow_code' => 'purchase_order_default'],
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
        'to_state_id' => $confirmed->id,
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
        'approvable_type' => PurchaseOrder::class,
        'code' => 'purchase_order_default',
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
    $this->submitter = createTestUserWithPermissions(['pipeline', 'purchase_order.edit']);
});

test('a new purchase order is auto-assigned to the po_lifecycle pipeline in draft', function () {
    setUpPurchaseOrderApprovalPipeline();
    Sanctum::actingAs($this->submitter, ['*']);

    $purchaseOrder = PurchaseOrder::factory()->create(['status' => 'draft']);

    $entityState = PipelineEntityState::query()
        ->where('entity_type', PurchaseOrder::class)
        ->where('entity_id', $purchaseOrder->id)
        ->first();

    expect($entityState)->not->toBeNull()
        ->and($entityState->currentState->code)->toBe('draft');
});

test('entity-states endpoint exposes the purchase order draft state and Submit transition', function () {
    setUpPurchaseOrderApprovalPipeline();
    Sanctum::actingAs($this->submitter, ['*']);

    $purchaseOrder = PurchaseOrder::factory()->create(['status' => 'draft']);

    getJson("/api/entity-states/purchase_order/{$purchaseOrder->id}")
        ->assertOk()
        ->assertJsonPath('data.current_state.code', 'draft')
        ->assertJsonPath('data.available_transitions.0.name', 'Submit');
});

test('submitting a purchase order creates a pending approval and sets status to pending_approval', function () {
    $setup = setUpPurchaseOrderApprovalPipeline();
    Sanctum::actingAs($this->submitter, ['*']);

    $purchaseOrder = PurchaseOrder::factory()->create(['status' => 'draft']);

    postJson("/api/entity-states/purchase_order/{$purchaseOrder->id}/transition", [
        'transition_id' => $setup['submit']->id,
    ])->assertOk();

    expect($purchaseOrder->fresh()->status)->toBe('pending_approval')
        ->and(ApprovalRequest::query()
            ->where('approvable_type', PurchaseOrder::class)
            ->where('approvable_id', $purchaseOrder->id)
            ->where('status', 'pending')
            ->exists())->toBeTrue();
});

test('approving the purchase order sets status to confirmed and records approved_by', function () {
    $setup = setUpPurchaseOrderApprovalPipeline();
    Sanctum::actingAs($this->submitter, ['*']);

    $purchaseOrder = PurchaseOrder::factory()->create(['status' => 'draft']);

    postJson("/api/entity-states/purchase_order/{$purchaseOrder->id}/transition", [
        'transition_id' => $setup['submit']->id,
    ])->assertOk();

    $approval = ApprovalRequest::query()
        ->where('approvable_type', PurchaseOrder::class)
        ->where('approvable_id', $purchaseOrder->id)
        ->firstOrFail();

    Sanctum::actingAs($setup['approver'], ['*']);

    postJson("/api/my-approvals/{$approval->id}/approve")->assertOk();

    $purchaseOrder->refresh();

    expect($purchaseOrder->status)->toBe('confirmed')
        ->and($purchaseOrder->approved_by)->toBe($setup['approver']->id)
        ->and($purchaseOrder->approved_at)->not->toBeNull();
});

test('rejecting the purchase order sets status to rejected', function () {
    $setup = setUpPurchaseOrderApprovalPipeline();
    Sanctum::actingAs($this->submitter, ['*']);

    $purchaseOrder = PurchaseOrder::factory()->create(['status' => 'draft']);

    postJson("/api/entity-states/purchase_order/{$purchaseOrder->id}/transition", [
        'transition_id' => $setup['submit']->id,
    ])->assertOk();

    $approval = ApprovalRequest::query()
        ->where('approvable_type', PurchaseOrder::class)
        ->where('approvable_id', $purchaseOrder->id)
        ->firstOrFail();

    Sanctum::actingAs($setup['approver'], ['*']);

    postJson("/api/my-approvals/{$approval->id}/reject", [
        'comments' => 'Harga tidak sesuai',
    ])->assertOk();

    expect($purchaseOrder->fresh()->status)->toBe('rejected');
});
