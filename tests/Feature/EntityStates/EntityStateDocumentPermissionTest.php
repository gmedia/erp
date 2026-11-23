<?php

use App\Models\Asset;
use App\Models\Pipeline;
use App\Models\PipelineState;
use App\Models\PipelineTransition;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('pipeline');

/**
 * Build a minimal pipeline for $entityClass with one Submit transition draft -> pending.
 *
 * @param  class-string  $entityClass
 * @return array{submit: PipelineTransition}
 */
function setUpDocumentSubmitPipeline(string $code, string $entityClass, ?string $requiredPermission): array
{
    $pipeline = Pipeline::factory()->create([
        'code' => $code,
        'entity_type' => $entityClass,
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
        'required_permission' => $requiredPermission,
    ]);

    return ['submit' => $submit];
}

/**
 * @return array{submit: PipelineTransition}
 */
function setUpPurchaseOrderSubmitPipeline(?string $requiredPermission = 'purchase_order.edit'): array
{
    return setUpDocumentSubmitPipeline('po_lifecycle', PurchaseOrder::class, $requiredPermission);
}

/**
 * @return array{submit: PipelineTransition}
 */
function setUpPurchaseRequestSubmitPipeline(?string $requiredPermission = 'purchase_request.edit'): array
{
    return setUpDocumentSubmitPipeline('pr_lifecycle', PurchaseRequest::class, $requiredPermission);
}

test('user with purchase_order.edit (no pipeline permission) can submit a purchase order', function () {
    $setup = setUpPurchaseOrderSubmitPipeline();
    $user = createTestUserWithPermissions(['purchase_order', 'purchase_order.edit']);
    Sanctum::actingAs($user, ['*']);

    $purchaseOrder = PurchaseOrder::factory()->create(['status' => 'draft']);

    postJson("/api/entity-states/purchase_order/{$purchaseOrder->id}/transition", [
        'transition_id' => $setup['submit']->id,
    ])->assertOk();
});

test('user with purchase_request.edit (no pipeline permission) can submit a purchase request', function () {
    $setup = setUpPurchaseRequestSubmitPipeline();
    $user = createTestUserWithPermissions(['purchase_request', 'purchase_request.edit']);
    Sanctum::actingAs($user, ['*']);

    $purchaseRequest = PurchaseRequest::factory()->create(['status' => 'draft']);

    postJson("/api/entity-states/purchase_request/{$purchaseRequest->id}/transition", [
        'transition_id' => $setup['submit']->id,
    ])->assertOk();
});

test('user with purchase_order module permission but without purchase_order.edit cannot submit a purchase order', function () {
    $setup = setUpPurchaseOrderSubmitPipeline();
    $user = createTestUserWithPermissions(['purchase_order']);
    Sanctum::actingAs($user, ['*']);

    $purchaseOrder = PurchaseOrder::factory()->create(['status' => 'draft']);

    // Transition-level required_permission is enforced by ExecuteTransitionAction (422).
    postJson("/api/entity-states/purchase_order/{$purchaseOrder->id}/transition", [
        'transition_id' => $setup['submit']->id,
    ])->assertStatus(422);

    expect($purchaseOrder->fresh()->status)->toBe('draft');
});

test('user with purchase_order permission cannot access purchase_request entity states', function () {
    $setup = setUpPurchaseRequestSubmitPipeline();
    $user = createTestUserWithPermissions(['purchase_order', 'purchase_order.edit']);
    Sanctum::actingAs($user, ['*']);

    $purchaseRequest = PurchaseRequest::factory()->create(['status' => 'draft']);

    getJson("/api/entity-states/purchase_request/{$purchaseRequest->id}")->assertStatus(403);

    postJson("/api/entity-states/purchase_request/{$purchaseRequest->id}/transition", [
        'transition_id' => $setup['submit']->id,
    ])->assertStatus(403);
});

test('user with purchase_order permission cannot access asset entity states', function () {
    $user = createTestUserWithPermissions(['purchase_order', 'purchase_order.edit']);
    Sanctum::actingAs($user, ['*']);

    $asset = Asset::factory()->create();

    getJson("/api/entity-states/asset/{$asset->id}")->assertStatus(403);
});

test('user with purchase_order permission cannot access pipeline CRUD endpoints', function () {
    $user = createTestUserWithPermissions(['purchase_order', 'purchase_order.edit']);
    Sanctum::actingAs($user, ['*']);

    getJson('/api/pipelines')->assertStatus(403);
});

test('user without any relevant permission cannot access purchase order entity states', function () {
    $setup = setUpPurchaseOrderSubmitPipeline();
    $user = createTestUserWithPermissions([]);
    Sanctum::actingAs($user, ['*']);

    $purchaseOrder = PurchaseOrder::factory()->create(['status' => 'draft']);

    getJson("/api/entity-states/purchase_order/{$purchaseOrder->id}")->assertStatus(403);

    postJson("/api/entity-states/purchase_order/{$purchaseOrder->id}/transition", [
        'transition_id' => $setup['submit']->id,
    ])->assertStatus(403);
});

test('purchase order transition without required_permission is denied for user without pipeline permission', function () {
    $setup = setUpPurchaseOrderSubmitPipeline(null);
    $user = createTestUserWithPermissions(['purchase_order', 'purchase_order.edit']);
    Sanctum::actingAs($user, ['*']);

    $purchaseOrder = PurchaseOrder::factory()->create(['status' => 'draft']);

    postJson("/api/entity-states/purchase_order/{$purchaseOrder->id}/transition", [
        'transition_id' => $setup['submit']->id,
    ])->assertStatus(403);
});

test('asset entity states still require the pipeline permission', function () {
    $user = createTestUserWithPermissions(['asset', 'asset.edit']);
    Sanctum::actingAs($user, ['*']);

    $asset = Asset::factory()->create();

    getJson("/api/entity-states/asset/{$asset->id}")->assertStatus(403);
});
