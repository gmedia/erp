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

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('purchase-orders');

/**
 * Pipeline po_lifecycle: draft -> pending_approval (Submit, trigger_approval),
 * serta state penerimaan yang tidak boleh dicapai lewat transisi manual.
 *
 * @return array{pipeline: Pipeline, draft: PipelineState, submit: PipelineTransition}
 */
function bypassSetUpPoPipeline(): array
{
    $pipeline = Pipeline::factory()->create([
        'code' => 'po_lifecycle',
        'entity_type' => PurchaseOrder::class,
        'is_active' => true,
    ]);

    $draft = PipelineState::factory()->create([
        'pipeline_id' => $pipeline->id, 'code' => 'draft', 'type' => 'initial', 'sort_order' => 0,
    ]);
    $pending = PipelineState::factory()->create([
        'pipeline_id' => $pipeline->id, 'code' => 'pending_approval', 'type' => 'intermediate', 'sort_order' => 10,
    ]);
    PipelineState::factory()->create([
        'pipeline_id' => $pipeline->id, 'code' => 'confirmed', 'type' => 'intermediate', 'sort_order' => 20,
    ]);
    PipelineState::factory()->create([
        'pipeline_id' => $pipeline->id, 'code' => 'fully_received', 'type' => 'intermediate', 'sort_order' => 30,
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

    return ['pipeline' => $pipeline, 'draft' => $draft, 'submit' => $submit];
}

function bypassCreatePoApprovalFlow(string $code = 'purchase_order_default'): ApprovalFlow
{
    $flow = ApprovalFlow::factory()->create([
        'approvable_type' => PurchaseOrder::class,
        'code' => $code,
        'is_active' => true,
        'conditions' => null,
    ]);

    ApprovalFlowStep::factory()->create([
        'approval_flow_id' => $flow->id,
        'step_order' => 1,
        'approver_type' => 'user',
        'approver_user_id' => User::factory()->create()->id,
    ]);

    return $flow;
}

function bypassPoPipelineStateCode(PurchaseOrder $purchaseOrder): ?string
{
    return PipelineEntityState::query()
        ->where('entity_type', PurchaseOrder::class)
        ->where('entity_id', $purchaseOrder->id)
        ->first()
        ?->currentState?->code;
}

/**
 * Transisi manual dari draft ke state target, tanpa aksi trigger_approval.
 */
function bypassCreateManualTransition(Pipeline $pipeline, PipelineState $from, string $targetCode): PipelineTransition
{
    $target = PipelineState::query()
        ->where('pipeline_id', $pipeline->id)
        ->where('code', $targetCode)
        ->firstOrFail();

    return PipelineTransition::factory()->create([
        'pipeline_id' => $pipeline->id,
        'from_state_id' => $from->id,
        'to_state_id' => $target->id,
        'name' => 'Manual ' . $targetCode,
        'code' => 'manual_' . $targetCode,
        'required_permission' => null,
    ]);
}

function bypassTransitionPurchaseOrder(PurchaseOrder $purchaseOrder, PipelineTransition $transition)
{
    return postJson("/api/entity-states/purchase_order/{$purchaseOrder->id}/transition", [
        'transition_id' => $transition->id,
    ]);
}

beforeEach(function () {
    $user = createTestUserWithPermissions(['pipeline', 'purchase_order.edit']);
    Sanctum::actingAs($user, ['*']);
});

test('manual pipeline transition from draft to a receiving state is rejected with 422', function () {
    $setup = bypassSetUpPoPipeline();

    $purchaseOrder = PurchaseOrder::factory()->create(['status' => 'draft']);
    $manual = bypassCreateManualTransition($setup['pipeline'], $setup['draft'], 'fully_received');

    bypassTransitionPurchaseOrder($purchaseOrder, $manual)->assertUnprocessable();

    expect($purchaseOrder->fresh()->status)->toBe('draft')
        ->and(bypassPoPipelineStateCode($purchaseOrder))->toBe('draft');
});

test('manual pipeline transition from draft to confirmed is rejected with 422', function () {
    $setup = bypassSetUpPoPipeline();

    $purchaseOrder = PurchaseOrder::factory()->create(['status' => 'draft']);
    $manual = bypassCreateManualTransition($setup['pipeline'], $setup['draft'], 'confirmed');

    bypassTransitionPurchaseOrder($purchaseOrder, $manual)->assertUnprocessable();

    expect($purchaseOrder->fresh()->status)->toBe('draft')
        ->and(bypassPoPipelineStateCode($purchaseOrder))->toBe('draft');
});

test('submit is rejected and leaves the purchase order in draft when no approval flow exists', function () {
    $setup = bypassSetUpPoPipeline();

    $purchaseOrder = PurchaseOrder::factory()->create(['status' => 'draft']);

    bypassTransitionPurchaseOrder($purchaseOrder, $setup['submit'])->assertUnprocessable();

    expect($purchaseOrder->fresh()->status)->toBe('draft')
        ->and(bypassPoPipelineStateCode($purchaseOrder))->toBe('draft')
        ->and(ApprovalRequest::query()->where('approvable_id', $purchaseOrder->id)->exists())->toBeFalse();
});

test('submit is rejected and leaves the purchase order in draft when the approval flow is inactive', function () {
    $setup = bypassSetUpPoPipeline();
    bypassCreatePoApprovalFlow()->update(['is_active' => false]);

    $purchaseOrder = PurchaseOrder::factory()->create(['status' => 'draft']);

    bypassTransitionPurchaseOrder($purchaseOrder, $setup['submit'])->assertUnprocessable();

    expect($purchaseOrder->fresh()->status)->toBe('draft')
        ->and(ApprovalRequest::query()->where('approvable_id', $purchaseOrder->id)->exists())->toBeFalse();
});

test('legacy trigger_approval config keyed as approval_flow_code selects the named flow', function () {
    $setup = bypassSetUpPoPipeline();
    bypassCreatePoApprovalFlow('purchase_order_default');
    bypassCreatePoApprovalFlow('purchase_order_other');

    PipelineTransitionAction::query()
        ->where('pipeline_transition_id', $setup['submit']->id)
        ->where('action_type', 'trigger_approval')
        ->update(['config' => ['approval_flow_code' => 'purchase_order_default']]);

    $purchaseOrder = PurchaseOrder::factory()->create(['status' => 'draft']);

    bypassTransitionPurchaseOrder($purchaseOrder, $setup['submit'])->assertOk();

    $request = ApprovalRequest::query()
        ->where('approvable_type', PurchaseOrder::class)
        ->where('approvable_id', $purchaseOrder->id)
        ->with('flow')
        ->firstOrFail();

    expect($request->flow->code)->toBe('purchase_order_default');
});
