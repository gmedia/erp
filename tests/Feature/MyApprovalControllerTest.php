<?php

use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestStep;
use App\Models\ApprovalFlowStep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('my-approvals');

it('displays the my approvals inbox page', function () {
    $user = User::factory()->create();

    \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);
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

    \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);
    $response = getJson('/api/my-approvals');
    
    $response->assertStatus(200);
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

    \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);
    postJson("/api/my-approvals/{$request->id}/approve", [
            'comments' => 'Looks good to me'
        ])
        ->assertSuccessful();
        
    expect($step->fresh()->status)->toBe('approved');
    expect($step->fresh()->comments)->toBe('Looks good to me');
    
    $this->assertDatabaseHas('approval_audit_logs', [
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

    \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);
    postJson("/api/my-approvals/{$request->id}/reject", [
            'comments' => 'Missing information'
        ])
        ->assertSuccessful();
        
    expect($step->fresh()->status)->toBe('rejected');
    expect($request->fresh()->status)->toBe('rejected');
});
