<?php

namespace Tests\Unit\Requests\ApprovalDelegations;

use App\Http\Requests\ApprovalDelegations\UpdateApprovalDelegationRequest;

uses()->group('approval-delegations');

test('authorize returns true', function () {
    $request = new UpdateApprovalDelegationRequest;
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new UpdateApprovalDelegationRequest;

    expect($request->rules())->toBe([
        'delegator_user_id' => ['sometimes', 'required', 'exists:users,id'],
        'delegate_user_id' => ['sometimes', 'required', 'exists:users,id', 'different:delegator_user_id'],
        'approvable_type' => ['sometimes', 'nullable', 'string', 'max:255'],
        'start_date' => ['sometimes', 'required', 'date'],
        'end_date' => ['sometimes', 'required', 'date', 'after_or_equal:start_date'],
        'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
        'is_active' => ['sometimes', 'boolean'],
    ]);
});
