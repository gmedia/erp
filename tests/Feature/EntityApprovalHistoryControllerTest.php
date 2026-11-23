<?php

use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\ApprovalRequest;
use App\Models\Asset;
use App\Models\Pipeline;
use App\Models\PipelineState;
use App\Models\PipelineTransition;
use App\Models\PipelineTransitionAction;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class)->group('approval-history');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['pipeline']);
});

test('can get approval history for supported entity', function () {
    $asset = Asset::factory()->create();
    $approvalRequest = ApprovalRequest::factory()->create([
        'approvable_type' => Asset::class,
        'approvable_id' => $asset->id,
    ]);

    actingAs($this->user)
        ->getJson("/api/entity-states/asset/{$asset->id}/approvals")
        ->assertOk()
        ->assertJsonPath('data.0.id', $approvalRequest->id);
});

test('can get approval history for purchase order and purchase request after submit', function (
    string $entityType,
    string $modelClass,
    string $pipelineCode,
    string $flowCode,
    string $transitionAlias,
) {
    $submitter = createTestUserWithPermissions(['pipeline', "{$entityType}.edit"]);
    $approver = User::factory()->create();

    $pipeline = Pipeline::factory()->create([
        'code' => $pipelineCode,
        'entity_type' => $modelClass,
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
        'config' => ['flow_code' => $flowCode],
    ]);
    PipelineTransitionAction::factory()->create([
        'pipeline_transition_id' => $submit->id,
        'action_type' => 'update_field',
        'execution_order' => 10,
        'config' => ['field' => 'status', 'value' => 'pending_approval'],
    ]);

    $flow = ApprovalFlow::factory()->create([
        'approvable_type' => $modelClass,
        'code' => $flowCode,
        'is_active' => true,
        'conditions' => null,
    ]);
    ApprovalFlowStep::factory()->create([
        'approval_flow_id' => $flow->id,
        'step_order' => 1,
        'approver_type' => 'user',
        'approver_user_id' => $approver->id,
    ]);

    $document = $modelClass::factory()->create(['status' => 'draft']);

    actingAs($submitter)
        ->postJson("/api/entity-states/{$transitionAlias}/{$document->id}/transition", [
            'transition_id' => $submit->id,
        ])
        ->assertOk();

    $approvalRequest = ApprovalRequest::query()
        ->where('approvable_type', $modelClass)
        ->where('approvable_id', $document->id)
        ->firstOrFail();

    actingAs($submitter)
        ->getJson("/api/entity-states/{$transitionAlias}/{$document->id}/approvals")
        ->assertOk()
        ->assertJsonPath('data.0.id', $approvalRequest->id)
        ->assertJsonPath('data.0.status', 'pending');
})->with([
    'purchase order' => [
        'purchase_order', PurchaseOrder::class, 'po_lifecycle', 'purchase_order_default', 'purchase_order',
    ],
    'purchase request' => [
        'purchase_request', PurchaseRequest::class, 'pr_lifecycle', 'purchase_request_default', 'purchase_request',
    ],
]);

test('returns 400 for unsupported entity type', function () {
    $asset = Asset::factory()->create();

    actingAs($this->user)
        ->getJson("/api/entity-states/unsupported/{$asset->id}/approvals")
        ->assertStatus(400)
        ->assertJson([
            'message' => 'Entity type not supported for approvals.',
        ]);
});

test('cannot access without permission', function () {
    $unprivilegedUser = createTestUserWithPermissions([]);
    $asset = Asset::factory()->create();

    actingAs($unprivilegedUser)
        ->getJson("/api/entity-states/asset/{$asset->id}/approvals")
        ->assertForbidden();
});

test('unauthenticated user cannot access endpoint', function () {
    $asset = Asset::factory()->create();

    getJson("/api/entity-states/asset/{$asset->id}/approvals")
        ->assertUnauthorized();
});
