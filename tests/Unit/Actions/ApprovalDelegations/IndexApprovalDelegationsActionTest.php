<?php

use App\Actions\ApprovalDelegations\IndexApprovalDelegationsAction;
use App\Http\Requests\ApprovalDelegations\IndexApprovalDelegationRequest;
use App\Models\ApprovalDelegation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('approval-delegations');

test('executes query with sorting and pagination', function () {
    ApprovalDelegation::factory()->count(15)->create();

    $action = app(IndexApprovalDelegationsAction::class);
    $request = Mockery::mock(IndexApprovalDelegationRequest::class);
    $request->shouldReceive('validated')->once()->andReturn([
        'per_page' => 10,
        'page' => 1,
        'sort_by' => 'created_at',
        'sort_direction' => 'desc',
    ]);
    $request->shouldReceive('get')->with('per_page', 10)->once()->andReturn(10);
    $request->shouldReceive('get')->with('page', 1)->once()->andReturn(1);

    $result = $action->execute($request);

    expect($result->total())->toBe(15)
        ->and($result->items())->toHaveCount(10);
});

test('executes query with search filter', function () {
    ApprovalDelegation::factory()->create(['reason' => 'Annual Leave']);
    ApprovalDelegation::factory()->create(['reason' => 'Business Trip']);

    $action = app(IndexApprovalDelegationsAction::class);
    $request = Mockery::mock(IndexApprovalDelegationRequest::class);
    $request->shouldReceive('validated')->once()->andReturn(['search' => 'Annual']);
    $request->shouldReceive('get')->with('per_page', 10)->once()->andReturn(10);
    $request->shouldReceive('get')->with('page', 1)->once()->andReturn(1);
    $result = $action->execute($request);

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->reason)->toBe('Annual Leave');
});

test('executes query with delegator filter', function () {
    $delegator = User::factory()->create();
    ApprovalDelegation::factory()->create(['delegator_user_id' => $delegator->id]);
    ApprovalDelegation::factory()->create();

    $action = app(IndexApprovalDelegationsAction::class);
    $request = Mockery::mock(IndexApprovalDelegationRequest::class);
    $request->shouldReceive('validated')->once()->andReturn(['delegator_user_id' => $delegator->id]);
    $request->shouldReceive('get')->with('per_page', 10)->once()->andReturn(10);
    $request->shouldReceive('get')->with('page', 1)->once()->andReturn(1);
    $result = $action->execute($request);

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->delegator_user_id)->toBe($delegator->id);
});

test('uses the approval delegation default pagination when per page is missing', function () {
    ApprovalDelegation::factory()->count(15)->create();

    $action = app(IndexApprovalDelegationsAction::class);
    $request = Mockery::mock(IndexApprovalDelegationRequest::class);
    $request->shouldReceive('validated')->once()->andReturn([]);
    $request->shouldReceive('get')->with('per_page', 10)->once()->andReturn(10);
    $request->shouldReceive('get')->with('page', 1)->once()->andReturn(1);
    $result = $action->execute($request);

    expect($result->total())->toBe(15)
        ->and($result->items())->toHaveCount(10)
        ->and($result->perPage())->toBe(10);
});
