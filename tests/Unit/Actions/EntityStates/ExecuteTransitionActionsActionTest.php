<?php

use App\Actions\Approvals\TriggerApprovalAction;
use App\Actions\EntityStates\ExecuteTransitionActionsAction;
use App\Models\Asset;
use App\Models\PipelineTransition;
use App\Models\PipelineTransitionAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use Tests\Unit\Actions\EntityStates\Fixtures\CustomTransitionAction;

uses(RefreshDatabase::class)->group('entity-state-actions');

beforeEach(function () {
    CustomTransitionAction::$calls = [];

    $this->triggerApproval = $this->mock(TriggerApprovalAction::class);
    $this->action = new ExecuteTransitionActionsAction($this->triggerApproval);

    $this->transition = PipelineTransition::factory()->create();
    $this->asset = Asset::factory()->create(['status' => 'draft']);
});

test('returns empty result array when transition has no actions', function () {
    expect($this->action->execute($this->transition, $this->asset))->toBe([]);
});

test('update_field action mutates entity and persists change', function () {
    $def = PipelineTransitionAction::factory()->create([
        'pipeline_transition_id' => $this->transition->id,
        'action_type' => 'update_field',
        'config' => ['field' => 'status', 'value' => 'active'],
    ]);

    $results = $this->action->execute($this->transition, $this->asset);

    expect($results)->toHaveKey($def->id)
        ->and($results[$def->id]['status'])->toBe('success');

    $this->asset->refresh();
    expect($this->asset->status)->toBe('active');
});

test('update_field throws when field or value missing and on_failure=abort re-throws', function () {
    PipelineTransitionAction::factory()->create([
        'pipeline_transition_id' => $this->transition->id,
        'action_type' => 'update_field',
        'config' => ['field' => 'status'],
        'on_failure' => 'abort',
    ]);

    Log::spy();

    expect(fn () => $this->action->execute($this->transition, $this->asset))
        ->toThrow(InvalidArgumentException::class, "Missing 'field' or 'value'");

    Log::shouldHaveReceived('error')->once();
});

test('on_failure=continue swallows the failure and returns failed status', function () {
    $def = PipelineTransitionAction::factory()->create([
        'pipeline_transition_id' => $this->transition->id,
        'action_type' => 'update_field',
        'config' => ['field' => 'status'],
        'on_failure' => 'continue',
    ]);

    $results = $this->action->execute($this->transition, $this->asset);

    expect($results[$def->id]['status'])->toBe('failed');
    expect($results[$def->id]['error'])->toContain("Missing 'field' or 'value'");
});

test('on_failure=log_and_continue logs error and continues to next action', function () {
    Log::spy();

    $failingDef = PipelineTransitionAction::factory()->create([
        'pipeline_transition_id' => $this->transition->id,
        'action_type' => 'update_field',
        'config' => ['field' => 'status'],
        'on_failure' => 'log_and_continue',
        'execution_order' => 1,
    ]);

    $okDef = PipelineTransitionAction::factory()->create([
        'pipeline_transition_id' => $this->transition->id,
        'action_type' => 'update_field',
        'config' => ['field' => 'status', 'value' => 'active'],
        'on_failure' => 'abort',
        'execution_order' => 2,
    ]);

    $results = $this->action->execute($this->transition, $this->asset);

    expect($results[$failingDef->id]['status'])->toBe('failed');
    expect($results[$okDef->id]['status'])->toBe('success');
    Log::shouldHaveReceived('error')->once();

    $this->asset->refresh();
    expect($this->asset->status)->toBe('active');
});

test('not-implemented actions (create_record, send_notification, dispatch_job) log warning and report success', function (string $type) {
    Log::spy();

    $def = PipelineTransitionAction::factory()->create([
        'pipeline_transition_id' => $this->transition->id,
        'action_type' => $type,
        'config' => [],
    ]);

    $results = $this->action->execute($this->transition, $this->asset);

    expect($results[$def->id]['status'])->toBe('success');
    expect($results[$def->id]['result'])->toBeTrue();

    Log::shouldHaveReceived('warning')->once();
})->with(['create_record', 'send_notification', 'dispatch_job']);

test('trigger_approval delegates to TriggerApprovalAction with the entity and params', function () {
    $params = ['flow_code' => 'AP-1', 'context' => 'unit-test'];

    /** @var MockInterface $mock */
    $mock = $this->triggerApproval;
    $mock->expects('execute')->once()->with($this->asset, $params);

    $def = PipelineTransitionAction::factory()->create([
        'pipeline_transition_id' => $this->transition->id,
        'action_type' => 'trigger_approval',
        'config' => $params,
    ]);

    $results = $this->action->execute($this->transition, $this->asset);

    expect($results[$def->id]['status'])->toBe('success');
});

test('custom action invokes the configured class method with entity and data', function () {
    $def = PipelineTransitionAction::factory()->create([
        'pipeline_transition_id' => $this->transition->id,
        'action_type' => 'custom',
        'config' => [
            'class' => CustomTransitionAction::class,
            'method' => 'run',
            'data' => ['payload' => 42],
        ],
    ]);

    $results = $this->action->execute($this->transition, $this->asset);

    expect($results[$def->id]['status'])->toBe('success');
    expect($results[$def->id]['result'])->toBe('custom-ok');
    expect(CustomTransitionAction::$calls)->toHaveCount(1);
    expect(CustomTransitionAction::$calls[0]['data'])->toBe(['payload' => 42]);
});

test('custom action with missing class or method config throws InvalidArgumentException', function () {
    PipelineTransitionAction::factory()->create([
        'pipeline_transition_id' => $this->transition->id,
        'action_type' => 'custom',
        'config' => ['class' => CustomTransitionAction::class],
        'on_failure' => 'abort',
    ]);

    expect(fn () => $this->action->execute($this->transition, $this->asset))
        ->toThrow(InvalidArgumentException::class, "Missing 'class' or 'method'");
});

test('custom action with non-existent class reports class-not-found', function () {
    $def = PipelineTransitionAction::factory()->create([
        'pipeline_transition_id' => $this->transition->id,
        'action_type' => 'custom',
        'config' => ['class' => 'App\\Nope\\DoesNotExist', 'method' => 'run'],
        'on_failure' => 'continue',
    ]);

    $results = $this->action->execute($this->transition, $this->asset);

    expect($results[$def->id]['status'])->toBe('failed');
    expect($results[$def->id]['error'])->toContain('Custom action class not found');
});

test('custom action with missing method reports method-not-found', function () {
    $def = PipelineTransitionAction::factory()->create([
        'pipeline_transition_id' => $this->transition->id,
        'action_type' => 'custom',
        'config' => ['class' => CustomTransitionAction::class, 'method' => 'no_such_method'],
        'on_failure' => 'continue',
    ]);

    $results = $this->action->execute($this->transition, $this->asset);

    expect($results[$def->id]['status'])->toBe('failed');
    expect($results[$def->id]['error'])->toContain('Custom action method not found');
});
