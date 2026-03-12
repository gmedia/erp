<?php

use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestStep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\artisan;

uses(RefreshDatabase::class)->group('approval-repair-command');

it('audits repairable approval requests without writing in dry run mode', function () {
    $flow = ApprovalFlow::factory()->create();
    $approverOne = User::factory()->create();
    $approverTwo = User::factory()->create();

    ApprovalFlowStep::factory()->create([
        'approval_flow_id' => $flow->id,
        'step_order' => 1,
        'approver_user_id' => $approverOne->id,
    ]);

    ApprovalFlowStep::factory()->create([
        'approval_flow_id' => $flow->id,
        'step_order' => 2,
        'approver_user_id' => $approverTwo->id,
    ]);

    $request = ApprovalRequest::factory()->create([
        'approval_flow_id' => $flow->id,
        'status' => 'pending',
        'current_step_order' => 1,
    ]);

    artisan('approvals:repair-missing-steps --dry-run')
        ->expectsOutputToContain('Found 1 approval request(s) missing steps.')
        ->expectsOutputToContain('repairable')
        ->expectsOutputToContain('Dry run only. Re-run without --dry-run to apply the repair.')
        ->assertSuccessful();

    expect($request->steps()->count())->toBe(0);
});

it('repairs pending approval requests that are missing request steps', function () {
    $flow = ApprovalFlow::factory()->create();
    $approverOne = User::factory()->create();
    $approverTwo = User::factory()->create();

    $flowStepOne = ApprovalFlowStep::factory()->create([
        'approval_flow_id' => $flow->id,
        'step_order' => 1,
        'approver_user_id' => $approverOne->id,
    ]);

    $flowStepTwo = ApprovalFlowStep::factory()->create([
        'approval_flow_id' => $flow->id,
        'step_order' => 2,
        'approver_user_id' => $approverTwo->id,
    ]);

    $request = ApprovalRequest::factory()->create([
        'approval_flow_id' => $flow->id,
        'status' => 'pending',
        'current_step_order' => 1,
    ]);

    artisan('approvals:repair-missing-steps')
        ->expectsOutputToContain('Found 1 approval request(s) missing steps.')
        ->expectsOutputToContain('Repaired: 1')
        ->assertSuccessful();

    $steps = ApprovalRequestStep::query()
        ->where('approval_request_id', $request->id)
        ->orderBy('step_order')
        ->get();

    expect($steps)->toHaveCount(2)
        ->and($steps[0]->approval_flow_step_id)->toBe($flowStepOne->id)
        ->and($steps[0]->status)->toBe('pending')
        ->and($steps[1]->approval_flow_step_id)->toBe($flowStepTwo->id)
        ->and($steps[1]->status)->toBe('pending');
});

it('skips requests that need manual review', function () {
    $flow = ApprovalFlow::factory()->create();
    $approverOne = User::factory()->create();
    $approverTwo = User::factory()->create();

    ApprovalFlowStep::factory()->create([
        'approval_flow_id' => $flow->id,
        'step_order' => 1,
        'approver_user_id' => $approverOne->id,
    ]);

    ApprovalFlowStep::factory()->create([
        'approval_flow_id' => $flow->id,
        'step_order' => 2,
        'approver_user_id' => $approverTwo->id,
    ]);

    $request = ApprovalRequest::factory()->create([
        'approval_flow_id' => $flow->id,
        'status' => 'in_progress',
        'current_step_order' => 2,
    ]);

    artisan('approvals:repair-missing-steps')
        ->expectsOutputToContain('manual_review_current_step_gt_1')
        ->expectsOutputToContain('Skipped: 1')
        ->assertSuccessful();

    expect($request->steps()->count())->toBe(0);
});
