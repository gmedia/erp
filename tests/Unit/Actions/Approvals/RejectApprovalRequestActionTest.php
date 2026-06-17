<?php

use App\Actions\Approvals\RejectApprovalRequestAction;
use App\Models\ApprovalAuditLog;
use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestStep;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class)->group('my-approvals');

beforeEach(function () {
    $this->action = new RejectApprovalRequestAction;
    $this->actor = User::factory()->create();
    $this->entity = PurchaseRequest::factory()->create();
});

function makeRejectableRequest(User $actor, PurchaseRequest $entity): ApprovalRequest
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

    $flowStep = ApprovalFlowStep::factory()->create([
        'approval_flow_id' => $flow->id,
        'step_order' => 1,
        'approver_type' => 'user',
        'approver_user_id' => $actor->id,
    ]);

    ApprovalRequestStep::create([
        'approval_request_id' => $request->id,
        'approval_flow_step_id' => $flowStep->id,
        'step_order' => 1,
        'status' => 'pending',
    ]);

    return $request;
}

test('reject marks step + request rejected and writes audit log', function () {
    $request = makeRejectableRequest($this->actor, $this->entity);
    $step = $request->steps()->firstOrFail();

    $this->action->execute($request, $step, $this->actor->id, 'missing receipts');

    $request->refresh();
    $step->refresh();

    expect($step->status)->toBe('rejected');
    expect($step->action)->toBe('reject');
    expect($step->acted_by)->toBe($this->actor->id);
    expect($step->comments)->toBe('missing receipts');
    expect($request->status)->toBe('rejected');
    expect($request->completed_at)->not->toBeNull();

    expect(ApprovalAuditLog::where('approval_request_id', $request->id)->where('event', 'step_rejected')->count())->toBe(1);
});

test('reject is atomic — rolls back step + request on failure', function () {
    $request = makeRejectableRequest($this->actor, $this->entity);
    $step = $request->steps()->firstOrFail();
    $action = $this->action;

    expect(function () use ($action, $request, $step) {
        DB::transaction(function () use ($action, $request, $step) {
            $action->execute($request, $step, $this->actor->id, 'will fail');
            throw new RuntimeException('boom');
        });
    })->toThrow(RuntimeException::class);

    $request->refresh();
    $step->refresh();

    expect($step->status)->toBe('pending');
    expect($request->status)->toBe('in_progress');
    expect(ApprovalAuditLog::where('approval_request_id', $request->id)->count())->toBe(0);
});
