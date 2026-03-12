<?php

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

it('displays the my approvals inbox page', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user, ['*']);
    getJson('/api/my-approvals')
        ->assertStatus(200);
});

it('lists pending approvals assigned to the current user', function () {
    $user = User::factory()->create();

    // Create a pending request
    $request = ApprovalRequest::factory()->create(['status' => 'pending']);

    // Create a pending step for the user
    $flowStep = ApprovalFlowStep::factory()->create([
        'approver_type' => 'user',
        'approver_user_id' => $user->id,
    ]);

    ApprovalRequestStep::factory()->create([
        'approval_request_id' => $request->id,
        'approval_flow_step_id' => $flowStep->id,
        'status' => 'pending',
    ]);

    Sanctum::actingAs($user, ['*']);
    $response = getJson('/api/my-approvals');

    $response->assertStatus(200);
});

it('only lists the current actionable step in pending approvals', function () {
    $currentApprover = User::factory()->create();
    $futureApprover = User::factory()->create();

    $request = ApprovalRequest::factory()->create([
        'status' => 'in_progress',
        'current_step_order' => 1,
    ]);

    $currentFlowStep = ApprovalFlowStep::factory()->create([
        'step_order' => 1,
        'approver_type' => 'user',
        'approver_user_id' => $currentApprover->id,
    ]);

    $futureFlowStep = ApprovalFlowStep::factory()->create([
        'step_order' => 2,
        'approver_type' => 'user',
        'approver_user_id' => $futureApprover->id,
    ]);

    ApprovalRequestStep::factory()->create([
        'approval_request_id' => $request->id,
        'approval_flow_step_id' => $currentFlowStep->id,
        'step_order' => 1,
        'status' => 'pending',
    ]);

    $futureStep = ApprovalRequestStep::factory()->create([
        'approval_request_id' => $request->id,
        'approval_flow_step_id' => $futureFlowStep->id,
        'step_order' => 2,
        'status' => 'pending',
    ]);

    Sanctum::actingAs($futureApprover, ['*']);

    getJson('/api/my-approvals')
        ->assertOk()
        ->assertJsonCount(0, 'pending')
        ->assertJsonMissing(['id' => $futureStep->id]);
});

it('can approve a pending request step', function () {
    $user = User::factory()->create();

    $request = ApprovalRequest::factory()->create(['status' => 'pending', 'current_step_order' => 1]);

    $flowStep = ApprovalFlowStep::factory()->create([
        'approver_type' => 'user',
        'approver_user_id' => $user->id,
    ]);

    $step = ApprovalRequestStep::factory()->create([
        'approval_request_id' => $request->id,
        'approval_flow_step_id' => $flowStep->id,
        'step_order' => 1,
        'status' => 'pending',
    ]);

    Sanctum::actingAs($user, ['*']);
    postJson("/api/my-approvals/{$request->id}/approve", [
        'comments' => 'Looks good to me',
    ])
        ->assertSuccessful();

    expect($step->fresh()->status)->toBe('approved');
    expect($step->fresh()->comments)->toBe('Looks good to me');

    assertDatabaseHas('approval_audit_logs', [
        'approval_request_id' => $request->id,
        'event' => 'completed',
    ]);
});

it('can reject a pending request step', function () {
    $user = User::factory()->create();

    $request = ApprovalRequest::factory()->create(['status' => 'pending', 'current_step_order' => 1]);

    $flowStep = ApprovalFlowStep::factory()->create([
        'approver_type' => 'user',
        'approver_user_id' => $user->id,
    ]);

    $step = ApprovalRequestStep::factory()->create([
        'approval_request_id' => $request->id,
        'approval_flow_step_id' => $flowStep->id,
        'step_order' => 1,
        'status' => 'pending',
    ]);

    Sanctum::actingAs($user, ['*']);
    postJson("/api/my-approvals/{$request->id}/reject", [
        'comments' => 'Missing information',
    ])
        ->assertSuccessful();

    expect($step->fresh()->status)->toBe('rejected');
    expect($request->fresh()->status)->toBe('rejected');
});

it('forbids approving a step that is not assigned to the current user', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();

    $request = ApprovalRequest::factory()->create([
        'status' => 'pending',
        'current_step_order' => 1,
    ]);

    $flowStep = ApprovalFlowStep::factory()->create([
        'approver_type' => 'user',
        'approver_user_id' => $owner->id,
    ]);

    $step = ApprovalRequestStep::factory()->create([
        'approval_request_id' => $request->id,
        'approval_flow_step_id' => $flowStep->id,
        'step_order' => 1,
        'status' => 'pending',
    ]);

    Sanctum::actingAs($intruder, ['*']);

    postJson("/api/my-approvals/{$request->id}/approve", [
        'comments' => 'Trying to approve another user step',
    ])->assertForbidden();

    expect($step->fresh()->status)->toBe('pending');
    expect($request->fresh()->status)->toBe('pending');
});

it('forbids rejecting a step that is not assigned to the current user', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();

    $request = ApprovalRequest::factory()->create([
        'status' => 'pending',
        'current_step_order' => 1,
    ]);

    $flowStep = ApprovalFlowStep::factory()->create([
        'approver_type' => 'user',
        'approver_user_id' => $owner->id,
    ]);

    $step = ApprovalRequestStep::factory()->create([
        'approval_request_id' => $request->id,
        'approval_flow_step_id' => $flowStep->id,
        'step_order' => 1,
        'status' => 'pending',
    ]);

    Sanctum::actingAs($intruder, ['*']);

    postJson("/api/my-approvals/{$request->id}/reject", [
        'comments' => 'Trying to reject another user step',
    ])->assertForbidden();

    expect($step->fresh()->status)->toBe('pending');
    expect($request->fresh()->status)->toBe('pending');
});
