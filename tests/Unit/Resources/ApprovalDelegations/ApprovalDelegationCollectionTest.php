<?php

use App\Http\Resources\ApprovalDelegations\ApprovalDelegationCollection;
use App\Models\ApprovalDelegation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('approval-delegations');

test('transforms approval delegation collection', function () {
    ApprovalDelegation::factory()->count(3)->create();

    $collection = new ApprovalDelegationCollection(ApprovalDelegation::paginate());
    $response = $collection->response()->getData(true);

    expect($response)->toHaveKeys(['data', 'links', 'meta'])
        ->and($response['data'])->toHaveCount(3);
});
