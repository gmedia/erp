<?php

use App\Models\ApprovalDelegation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class)->group('approval-delegations');

test('factory creates a valid approval delegation', function () {
    $delegation = ApprovalDelegation::factory()->create();

    assertDatabaseHas('approval_delegations', ['id' => $delegation->id]);

    expect($delegation->getAttributes())->toMatchArray([
        'delegator_user_id' => $delegation->delegator_user_id,
        'delegate_user_id' => $delegation->delegate_user_id,
        'approvable_type' => $delegation->approvable_type,
        'start_date' => $delegation->start_date->format('Y-m-d H:i:s'),
        'end_date' => $delegation->end_date->format('Y-m-d H:i:s'),
        'reason' => $delegation->reason,
        'is_active' => $delegation->is_active,
    ]);
});

test('approval delegation belongs to a delegator and delegate', function () {
    $delegator = User::factory()->create();
    $delegate = User::factory()->create();

    $delegation = ApprovalDelegation::factory()->create([
        'delegator_user_id' => $delegator->id,
        'delegate_user_id' => $delegate->id,
    ]);

    expect($delegation->delegator)->toBeInstanceOf(User::class)
        ->and($delegation->delegator->id)->toBe($delegator->id)
        ->and($delegation->delegate)->toBeInstanceOf(User::class)
        ->and($delegation->delegate->id)->toBe($delegate->id);
});

test('fillable attributes are defined correctly', function () {
    $fillable = (new ApprovalDelegation)->getFillable();

    expect($fillable)->toBe([
        'delegator_user_id',
        'delegate_user_id',
        'approvable_type',
        'start_date',
        'end_date',
        'reason',
        'is_active',
    ]);
});
