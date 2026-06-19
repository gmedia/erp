<?php

use App\Actions\Approvals\TriggerApprovalAction;
use App\Models\ApprovalAuditLog;
use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestStep;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class)->group('approvals');

beforeEach(function () {
    $this->action = app(TriggerApprovalAction::class);
    $this->actor = User::factory()->create();
    Sanctum::actingAs($this->actor, ['*']);
    $this->entity = PurchaseRequest::factory()->create();
});

function makeFlowWithSteps(int $steps = 2, array $overrides = []): ApprovalFlow
{
    /** @var ApprovalFlow $flow */
    $flow = ApprovalFlow::factory()->create(array_merge([
        'approvable_type' => PurchaseRequest::class,
    ], $overrides));

    for ($i = 1; $i <= $steps; $i++) {
        ApprovalFlowStep::factory()->create([
            'approval_flow_id' => $flow->id,
            'step_order' => $i,
        ]);
    }

    return $flow;
}

test('returns null and logs warning when no flow matches', function () {
    Log::spy();

    $result = $this->action->execute($this->entity, []);

    expect($result)->toBeNull();
    Log::shouldHaveReceived('warning')->once();
});

test('returns null and logs warning when matched flow has no steps', function () {
    Log::spy();

    ApprovalFlow::factory()->create([
        'approvable_type' => PurchaseRequest::class,
        'is_active' => true,
        'conditions' => null,
    ]);

    $result = $this->action->execute($this->entity, []);

    expect($result)->toBeNull();
    Log::shouldHaveReceived('warning')->once();
});

test('happy path creates request, request steps, and audit log when flow matches by code', function () {
    $flow = makeFlowWithSteps(2, ['code' => 'PR-STD']);

    $request = $this->action->execute($this->entity, ['flow_code' => 'PR-STD']);

    expect($request)->toBeInstanceOf(ApprovalRequest::class);
    expect($request->approval_flow_id)->toBe($flow->id);
    expect($request->approvable_type)->toBe($this->entity->getMorphClass());
    expect($request->approvable_id)->toBe($this->entity->getKey());
    expect($request->current_step_order)->toBe(1);
    expect($request->status)->toBe('pending');
    expect($request->submitted_by)->toBe($this->actor->id);

    expect(ApprovalRequestStep::where('approval_request_id', $request->id)->count())->toBe(2);

    $auditLog = ApprovalAuditLog::where('approval_request_id', $request->id)->first();
    expect($auditLog)->not->toBeNull();
    expect($auditLog->event)->toBe('submitted');
    expect($auditLog->actor_user_id)->toBe($this->actor->id);
});

test('returns null when flow_code is provided but no matching active flow exists', function () {
    Log::spy();

    makeFlowWithSteps(1, ['code' => 'PR-EXISTING']);

    $result = $this->action->execute($this->entity, ['flow_code' => 'PR-MISSING']);

    expect($result)->toBeNull();
    Log::shouldHaveReceived('warning')->once();
});

test('falls back to default conditional flow when no flow_code provided', function () {
    makeFlowWithSteps(1, ['conditions' => null, 'code' => 'PR-DEFAULT']);

    $request = $this->action->execute($this->entity, []);

    expect($request)->toBeInstanceOf(ApprovalRequest::class);
});

test('matches conditional flow when entity conditions evaluate to true', function () {
    $flow = makeFlowWithSteps(1, [
        'code' => 'PR-CONDITIONAL',
        'conditions' => [
            'field_checks' => [
                ['field' => 'status', 'operator' => 'equals', 'value' => $this->entity->status],
            ],
        ],
    ]);

    $request = $this->action->execute($this->entity, []);

    expect($request)->toBeInstanceOf(ApprovalRequest::class);
    expect($request->approval_flow_id)->toBe($flow->id);
});

test('skips conditional flow when conditions evaluate to false and returns null if no fallback', function () {
    Log::spy();

    makeFlowWithSteps(1, [
        'code' => 'PR-NO-MATCH',
        'conditions' => [
            'field_checks' => [
                ['field' => 'status', 'operator' => 'equals', 'value' => 'this-status-does-not-exist'],
            ],
        ],
    ]);

    $result = $this->action->execute($this->entity, []);

    expect($result)->toBeNull();
    Log::shouldHaveReceived('warning')->once();
});

test('inactive flows are not selected', function () {
    Log::spy();

    makeFlowWithSteps(1, ['code' => 'PR-INACTIVE', 'is_active' => false]);

    $result = $this->action->execute($this->entity, ['flow_code' => 'PR-INACTIVE']);

    expect($result)->toBeNull();
    Log::shouldHaveReceived('warning')->once();
});

test('flows for other approvable_type are not selected', function () {
    Log::spy();

    ApprovalFlow::factory()->create([
        'approvable_type' => 'App\\Models\\OtherEntity',
        'code' => 'OE-FLOW',
        'is_active' => true,
        'conditions' => null,
    ]);

    $result = $this->action->execute($this->entity, ['flow_code' => 'OE-FLOW']);

    expect($result)->toBeNull();
    Log::shouldHaveReceived('warning')->once();
});

test('newer flows are checked first when multiple default flows exist for the same entity type', function () {
    $older = makeFlowWithSteps(1, ['code' => 'PR-OLDER', 'conditions' => null]);
    $newer = makeFlowWithSteps(1, ['code' => 'PR-NEWER', 'conditions' => null]);

    $request = $this->action->execute($this->entity, []);

    expect($request)->toBeInstanceOf(ApprovalRequest::class);
    expect($request->approval_flow_id)->toBe($newer->id);
    expect($request->approval_flow_id)->not->toBe($older->id);
});
