<?php

use App\Http\Resources\ApprovalDelegations\ApprovalDelegationResource;
use App\Models\ApprovalDelegation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('approval-delegations');

test('transforms approval delegation model into array', function () {
    $delegation = ApprovalDelegation::factory()->create();

    $resource = new ApprovalDelegationResource($delegation);
    $array = $resource->toArray(request());

    expect($array)->toHaveKeys([
        'id',
        'approvable_type',
        'start_date',
        'end_date',
        'reason',
        'is_active',
        'created_at',
        'updated_at',
        'delegator',
        'delegate',
    ]);

    expect($array['delegator'])->toHaveKeys(['id', 'name'])
        ->and($array['delegate'])->toHaveKeys(['id', 'name']);
});
