<?php

use App\Models\ApprovalFlow;
use App\Models\User;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('approval-flows');

beforeEach(function () {
    $this->user = createTestUserWithPermissions([
        'approval_flow',
        'approval_flow.create',
        'approval_flow.edit',
        'approval_flow.delete',
    ]);
});

it('can list approval flows', function () {
    ApprovalFlow::factory()->count(3)->create();

    $response = $this->actingAs($this->user)
        ->getJson('/api/approval-flows');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

it('can create an approval flow', function () {
    $department = Department::factory()->create();
    $approver = User::factory()->create();

    $payload = [
        'name' => 'Standard Asset Request',
        'code' => 'std_asset_req',
        'approvable_type' => 'App\\Models\\AssetMovement',
        'description' => 'Test flow',
        'is_active' => true,
        'conditions' => null,
        'steps' => [
            [
                'name' => 'Manager Approval',
                'approver_type' => 'user',
                'approver_user_id' => $approver->id,
                'required_action' => 'approve',
                'auto_approve_after_hours' => null,
                'escalate_after_hours' => null,
                'escalation_user_id' => null,
                'can_reject' => true,
            ],
            [
                'name' => 'Dept Head Review',
                'approver_type' => 'department_head',
                'approver_department_id' => $department->id,
                'required_action' => 'review',
                'auto_approve_after_hours' => 24,
                'escalate_after_hours' => null,
                'escalation_user_id' => null,
                'can_reject' => false,
            ]
        ]
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/approval-flows', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'Standard Asset Request')
        ->assertJsonCount(2, 'data.steps');

    $this->assertDatabaseHas('approval_flows', [
        'code' => 'std_asset_req',
    ]);

    $this->assertDatabaseHas('approval_flow_steps', [
        'name' => 'Manager Approval',
        'approver_user_id' => $approver->id,
    ]);
});

it('can show an approval flow', function () {
    $flow = ApprovalFlow::factory()->create();

    $response = $this->actingAs($this->user)
        ->getJson("/api/approval-flows/{$flow->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $flow->id);
});

it('can update an approval flow', function () {
    $flow = ApprovalFlow::factory()->create(['name' => 'Old Name']);
    $flow->steps()->create([
        'step_order' => 1,
        'name' => 'Old Step',
        'approver_type' => 'user',
        'approver_user_id' => $this->user->id,
        'required_action' => 'approve',
        'can_reject' => true,
    ]);

    $payload = [
        'name' => 'New Name',
        'code' => $flow->code,
        'approvable_type' => $flow->approvable_type,
        'steps' => [
            [
                'name' => 'New Step 1',
                'approver_type' => 'role',
                'approver_role_id' => 1,
                'required_action' => 'review',
                'can_reject' => false,
            ]
        ]
    ];

    $response = $this->actingAs($this->user)
        ->putJson("/api/approval-flows/{$flow->id}", $payload);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', 'New Name');

    $this->assertDatabaseMissing('approval_flow_steps', [
        'name' => 'Old Step',
    ]);

    $this->assertDatabaseHas('approval_flow_steps', [
        'name' => 'New Step 1',
        'approver_type' => 'role',
    ]);
});

it('can delete an approval flow', function () {
    $flow = ApprovalFlow::factory()->create();

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/approval-flows/{$flow->id}");

    $response->assertStatus(204);

    $this->assertDatabaseMissing('approval_flows', [
        'id' => $flow->id,
    ]);
});

it('can export approval flows', function () {
    ApprovalFlow::factory()->count(2)->create();

    $response = $this->actingAs($this->user)
        ->postJson('/api/approval-flows/export');

    $response->assertStatus(200)
        ->assertJsonStructure(['url', 'filename']);
});
