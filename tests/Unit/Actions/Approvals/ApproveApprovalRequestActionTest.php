<?php

use App\Actions\Approvals\ApproveApprovalRequestAction;
use App\Models\ApprovalAuditLog;
use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestStep;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('my-approvals');

beforeEach(function () {
    $this->action = new ApproveApprovalRequestAction;
    $this->actor = User::factory()->create();
    $this->entity = PurchaseRequest::factory()->create();
});

function makeApprovalRequestWithSteps(int $stepCount, User $actor, PurchaseRequest $entity): ApprovalRequest
{
    /** @var ApprovalFlow $flow */
    $flow = ApprovalFlow::factory()->create(['approvable_type' => PurchaseRequest::class]);

    /** @var ApprovalRequest $request */
    $request = ApprovalRequest::create([
        'approval_flow_id' => $flow->id,
        'approvable_type' => PurchaseRequest::class,
        'approvable_id' => $entity->id,
        'submitted_by' => $actor->id,
        'status' => 'in_progress',
        'current_step_order' => 1,
    ]);

    for ($i = 1; $i <= $stepCount; $i++) {
        $flowStep = ApprovalFlowStep::factory()->create([
            'approval_flow_id' => $flow->id,
            'step_order' => $i,
            'approver_type' => 'user',
            'approver_user_id' => $actor->id,
        ]);

        ApprovalRequestStep::create([
            'approval_request_id' => $request->id,
            'approval_flow_step_id' => $flowStep->id,
            'step_order' => $i,
            'status' => 'pending',
        ]);
    }

    return $request;
}

test('approving a single-step request marks the request approved and logs completion', function () {
    $request = makeApprovalRequestWithSteps(1, $this->actor, $this->entity);
    $step = $request->steps()->where('step_order', 1)->firstOrFail();

    $this->action->execute($request, $step, $this->actor->id, 'looks good');

    $request->refresh();
    $step->refresh();

    expect($step->status)->toBe('approved');
    expect($step->action)->toBe('approve');
    expect($step->acted_by)->toBe($this->actor->id);
    expect($step->comments)->toBe('looks good');
    expect($request->status)->toBe('approved');
    expect($request->completed_at)->not->toBeNull();

    expect(ApprovalAuditLog::where('approval_request_id', $request->id)->where('event', 'step_approved')->count())->toBe(1);
    expect(ApprovalAuditLog::where('approval_request_id', $request->id)->where('event', 'completed')->count())->toBe(1);
});

test('approving a non-final step advances current_step_order and keeps request in_progress', function () {
    $request = makeApprovalRequestWithSteps(2, $this->actor, $this->entity);
    $step1 = $request->steps()->where('step_order', 1)->firstOrFail();

    $this->action->execute($request, $step1, $this->actor->id, null);

    $request->refresh();

    expect($request->status)->toBe('in_progress');
    expect($request->current_step_order)->toBe(2);
    expect($request->completed_at)->toBeNull();

    expect(ApprovalAuditLog::where('approval_request_id', $request->id)->where('event', 'completed')->count())->toBe(0);
});

test('approve persists null comments cleanly', function () {
    $request = makeApprovalRequestWithSteps(1, $this->actor, $this->entity);
    $step = $request->steps()->where('step_order', 1)->firstOrFail();

    $this->action->execute($request, $step, $this->actor->id, null);

    $step->refresh();
    expect($step->comments)->toBeNull();
});
