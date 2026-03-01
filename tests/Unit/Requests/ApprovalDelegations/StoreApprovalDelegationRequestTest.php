<?php

namespace Tests\Unit\Requests\ApprovalDelegations;

use App\Http\Requests\ApprovalDelegations\StoreApprovalDelegationRequest;
use Illuminate\Support\Facades\Validator;
uses()->group('approval-delegations');

test('authorize returns true', function () {
    $request = new StoreApprovalDelegationRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new StoreApprovalDelegationRequest();
    
    expect($request->rules())->toBe([
        'delegator_user_id' => ['required', 'exists:users,id'],
        'delegate_user_id' => ['required', 'exists:users,id', 'different:delegator_user_id'],
        'approvable_type' => ['nullable', 'string', 'max:255'],
        'start_date' => ['required', 'date'],
        'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        'reason' => ['nullable', 'string', 'max:255'],
        'is_active' => ['boolean'],
    ]);
});
