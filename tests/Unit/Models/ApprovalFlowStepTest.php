<?php

use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class)->group('approval-flows');

test('factory creates a valid approval flow step', function () {
    $step = ApprovalFlowStep::factory()->create();

    assertDatabaseHas('approval_flow_steps', ['id' => $step->id]);

    expect($step->getAttributes())->toMatchArray([
        'approval_flow_id' => $step->approval_flow_id,
        'name' => $step->name,
        'approver_type' => $step->approver_type,
        'required_action' => $step->required_action,
    ]);
});

test('approval flow step belongs to an approval flow', function () {
    $flow = ApprovalFlow::factory()->create();
    $step = ApprovalFlowStep::factory()->create(['approval_flow_id' => $flow->id]);

    expect($step->flow)->toBeInstanceOf(ApprovalFlow::class)
        ->and($step->flow->id)->toBe($flow->id);
});

test('approval flow step belongs to a user when approver type is user', function () {
    $user = User::factory()->create();
    $step = ApprovalFlowStep::factory()->create([
        'approver_type' => 'user',
        'approver_user_id' => $user->id,
    ]);

    expect($step->user)->toBeInstanceOf(User::class)
        ->and($step->user->id)->toBe($user->id);
});

test('fillable attributes are defined correctly', function () {
    $fillable = (new ApprovalFlowStep)->getFillable();

    expect($fillable)->toBe([
        'approval_flow_id',
        'step_order',
        'name',
        'approver_type',
        'approver_user_id',
        'approver_role_id',
        'approver_department_id',
        'required_action',
        'auto_approve_after_hours',
        'escalate_after_hours',
        'escalation_user_id',
        'can_reject',
    ]);
});
