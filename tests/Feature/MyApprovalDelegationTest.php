<?php

use App\Models\ApprovalDelegation;
use App\Models\ApprovalFlowStep;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestStep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('my-approvals');

/**
 * Build a single-step pending request assigned to $approver, submitted by $submitter.
 *
 * @return array{request: ApprovalRequest, step: ApprovalRequestStep}
 */
function makeSingleStepRequest(User $approver, User $submitter): array
{
    $request = ApprovalRequest::factory()->create([
        'status' => 'pending',
        'current_step_order' => 1,
        'submitted_by' => $submitter->id,
    ]);

    $flowStep = ApprovalFlowStep::factory()->create([
        'step_order' => 1,
        'approver_type' => 'user',
        'approver_user_id' => $approver->id,
    ]);

    $step = ApprovalRequestStep::factory()->create([
        'approval_request_id' => $request->id,
        'approval_flow_step_id' => $flowStep->id,
        'step_order' => 1,
        'status' => 'pending',
    ]);

    return ['request' => $request, 'step' => $step];
}

it('forbids the submitter from approving their own request step', function () {
    $submitter = User::factory()->create();
    ['request' => $request, 'step' => $step] = makeSingleStepRequest($submitter, $submitter);

    Sanctum::actingAs($submitter, ['*']);
    postJson("/api/my-approvals/{$request->id}/approve", ['comments' => 'self'])
        ->assertStatus(403);

    expect($step->fresh()->status)->toBe('pending');
});

it('forbids the submitter from rejecting their own request step', function () {
    $submitter = User::factory()->create();
    ['request' => $request, 'step' => $step] = makeSingleStepRequest($submitter, $submitter);

    Sanctum::actingAs($submitter, ['*']);
    postJson("/api/my-approvals/{$request->id}/reject", ['comments' => 'self'])
        ->assertStatus(403);

    expect($step->fresh()->status)->toBe('pending');
});

it('lets an active delegate act on a step delegated to them within the date range', function () {
    $approver = User::factory()->create();
    $delegate = User::factory()->create();
    $submitter = User::factory()->create();
    ['request' => $request, 'step' => $step] = makeSingleStepRequest($approver, $submitter);

    ApprovalDelegation::factory()->create([
        'delegator_user_id' => $approver->id,
        'delegate_user_id' => $delegate->id,
        'approvable_type' => null,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addDay()->toDateString(),
        'is_active' => true,
    ]);

    Sanctum::actingAs($delegate, ['*']);
    postJson("/api/my-approvals/{$request->id}/approve", ['comments' => 'via delegation'])
        ->assertSuccessful();

    $step->refresh();
    expect($step->status)->toBe('approved');
    expect($step->acted_by)->toBe($delegate->id);
    expect($step->delegated_from)->toBe($approver->id);
});

it('shows a delegated step in the delegate inbox', function () {
    $approver = User::factory()->create();
    $delegate = User::factory()->create();
    $submitter = User::factory()->create();
    makeSingleStepRequest($approver, $submitter);

    ApprovalDelegation::factory()->create([
        'delegator_user_id' => $approver->id,
        'delegate_user_id' => $delegate->id,
        'approvable_type' => null,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addDay()->toDateString(),
        'is_active' => true,
    ]);

    Sanctum::actingAs($delegate, ['*']);
    $response = getJson('/api/my-approvals')->assertOk();

    expect($response->json('pending'))->toHaveCount(1);
});

it('does not let a delegate act outside the delegation date range', function () {
    $approver = User::factory()->create();
    $delegate = User::factory()->create();
    $submitter = User::factory()->create();
    ['request' => $request, 'step' => $step] = makeSingleStepRequest($approver, $submitter);

    ApprovalDelegation::factory()->create([
        'delegator_user_id' => $approver->id,
        'delegate_user_id' => $delegate->id,
        'approvable_type' => null,
        'start_date' => now()->subDays(10)->toDateString(),
        'end_date' => now()->subDays(5)->toDateString(),
        'is_active' => true,
    ]);

    Sanctum::actingAs($delegate, ['*']);
    postJson("/api/my-approvals/{$request->id}/approve", ['comments' => 'expired'])
        ->assertStatus(403);

    expect($step->fresh()->status)->toBe('pending');
});

it('does not let a delegate act when the delegation is inactive', function () {
    $approver = User::factory()->create();
    $delegate = User::factory()->create();
    $submitter = User::factory()->create();
    ['request' => $request, 'step' => $step] = makeSingleStepRequest($approver, $submitter);

    ApprovalDelegation::factory()->create([
        'delegator_user_id' => $approver->id,
        'delegate_user_id' => $delegate->id,
        'approvable_type' => null,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addDay()->toDateString(),
        'is_active' => false,
    ]);

    Sanctum::actingAs($delegate, ['*']);
    postJson("/api/my-approvals/{$request->id}/reject", ['comments' => 'inactive'])
        ->assertStatus(403);

    expect($step->fresh()->status)->toBe('pending');
});

it('does not let a delegate act on a document type outside the delegation scope', function () {
    $approver = User::factory()->create();
    $delegate = User::factory()->create();
    $submitter = User::factory()->create();
    ['request' => $request, 'step' => $step] = makeSingleStepRequest($approver, $submitter);

    ApprovalDelegation::factory()->create([
        'delegator_user_id' => $approver->id,
        'delegate_user_id' => $delegate->id,
        'approvable_type' => 'App\\Models\\SomeOtherDocument',
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addDay()->toDateString(),
        'is_active' => true,
    ]);

    Sanctum::actingAs($delegate, ['*']);
    postJson("/api/my-approvals/{$request->id}/approve", ['comments' => 'out of scope'])
        ->assertStatus(403);

    expect($step->fresh()->status)->toBe('pending');
});

it('forbids a delegate who is also the submitter of the request', function () {
    $approver = User::factory()->create();
    $delegate = User::factory()->create();
    ['request' => $request, 'step' => $step] = makeSingleStepRequest($approver, $delegate);

    ApprovalDelegation::factory()->create([
        'delegator_user_id' => $approver->id,
        'delegate_user_id' => $delegate->id,
        'approvable_type' => null,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addDay()->toDateString(),
        'is_active' => true,
    ]);

    Sanctum::actingAs($delegate, ['*']);
    postJson("/api/my-approvals/{$request->id}/approve", ['comments' => 'self via delegate'])
        ->assertStatus(403);

    expect($step->fresh()->status)->toBe('pending');
});

it('does not chain delegations: a delegate of a delegate cannot act', function () {
    $approver = User::factory()->create();
    $firstDelegate = User::factory()->create();
    $secondDelegate = User::factory()->create();
    $submitter = User::factory()->create();
    ['request' => $request, 'step' => $step] = makeSingleStepRequest($approver, $submitter);

    ApprovalDelegation::factory()->create([
        'delegator_user_id' => $approver->id,
        'delegate_user_id' => $firstDelegate->id,
        'approvable_type' => null,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addDay()->toDateString(),
        'is_active' => true,
    ]);

    ApprovalDelegation::factory()->create([
        'delegator_user_id' => $firstDelegate->id,
        'delegate_user_id' => $secondDelegate->id,
        'approvable_type' => null,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addDay()->toDateString(),
        'is_active' => true,
    ]);

    Sanctum::actingAs($secondDelegate, ['*']);
    postJson("/api/my-approvals/{$request->id}/approve", ['comments' => 'chained'])
        ->assertStatus(403);

    expect($step->fresh()->status)->toBe('pending');
});

it('rejects a second approve on a step already acted on', function () {
    $approver = User::factory()->create();
    $submitter = User::factory()->create();
    ['request' => $request, 'step' => $step] = makeSingleStepRequest($approver, $submitter);

    Sanctum::actingAs($approver, ['*']);
    postJson("/api/my-approvals/{$request->id}/approve", ['comments' => 'first'])
        ->assertSuccessful();

    postJson("/api/my-approvals/{$request->id}/approve", ['comments' => 'second'])
        ->assertStatus(403);
    // A second decision on an already-acted step is refused by the resolver before the lock
    // (step no longer pending for the current order), so it stays 403, never overwriting.

    expect($step->fresh()->comments)->toBe('first');
    assertDatabaseHas('approval_audit_logs', [
        'approval_request_id' => $request->id,
        'event' => 'step_approved',
    ]);
});

it('flags a pending step as delegated only for the delegate, not the direct approver', function () {
    $approver = User::factory()->create();
    $delegate = User::factory()->create();
    $submitter = User::factory()->create();
    makeSingleStepRequest($approver, $submitter);

    ApprovalDelegation::factory()->create([
        'delegator_user_id' => $approver->id,
        'delegate_user_id' => $delegate->id,
        'approvable_type' => null,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addDay()->toDateString(),
        'is_active' => true,
    ]);

    Sanctum::actingAs($delegate, ['*']);
    $delegateResponse = getJson('/api/my-approvals')->assertOk();
    expect($delegateResponse->json('pending.0.is_delegated'))->toBeTrue();

    Sanctum::actingAs($approver, ['*']);
    $approverResponse = getJson('/api/my-approvals')->assertOk();
    expect($approverResponse->json('pending.0.is_delegated'))->toBeFalse();
});

it('omits a delegated step from the inbox when the delegate is its submitter', function () {
    $approver = User::factory()->create();
    $delegate = User::factory()->create();
    makeSingleStepRequest($approver, $delegate);

    ApprovalDelegation::factory()->create([
        'delegator_user_id' => $approver->id,
        'delegate_user_id' => $delegate->id,
        'approvable_type' => null,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addDay()->toDateString(),
        'is_active' => true,
    ]);

    Sanctum::actingAs($delegate, ['*']);
    $response = getJson('/api/my-approvals')->assertOk();

    expect($response->json('pending'))->toBeEmpty();
});

it('omits a delegated step from the inbox when the delegation covers another document type', function () {
    $approver = User::factory()->create();
    $delegate = User::factory()->create();
    $submitter = User::factory()->create();
    ['request' => $request] = makeSingleStepRequest($approver, $submitter);

    ApprovalDelegation::factory()->create([
        'delegator_user_id' => $approver->id,
        'delegate_user_id' => $delegate->id,
        'approvable_type' => 'App\\Models\\SomeOtherDocument',
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addDay()->toDateString(),
        'is_active' => true,
    ]);

    expect($request->approvable_type)->not->toBe('App\\Models\\SomeOtherDocument');

    Sanctum::actingAs($delegate, ['*']);
    $response = getJson('/api/my-approvals')->assertOk();

    expect($response->json('pending'))->toBeEmpty();
});
