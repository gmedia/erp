<?php

use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestStep;
use App\Models\ApprovalFlowStep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class)->group('my-approvals');

it('displays the my approvals inbox page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/my-approvals')
        ->assertStatus(200)
        ->assertInertia(fn (AssertableInertia $page) => $page->component('my-approvals/index'));
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

    $this->actingAs($user)
        ->get('/my-approvals')
        ->assertStatus(200)
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('my-approvals/index')
            ->has('pending', 1)
        );
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

    $this->actingAs($user)
        ->post("/my-approvals/{$request->id}/approve", [
            'comments' => 'Looks good to me'
        ])
        ->assertRedirect();
        
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

    $this->actingAs($user)
        ->post("/my-approvals/{$request->id}/reject", [
            'comments' => 'Missing information'
        ])
        ->assertRedirect();
        
    expect($step->fresh()->status)->toBe('rejected');
    expect($request->fresh()->status)->toBe('rejected');
});
